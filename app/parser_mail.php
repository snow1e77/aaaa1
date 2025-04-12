<?php
// Если DOCUMENT_ROOT не определён (например, при запуске из CLI), задаём его
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
}

// Включаем вывод ошибок
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Подключаем БД
require_once __DIR__ . '/../include/db.php';

// Гарантируем использование нужной кодировки для работы с эмодзи (4-байтовые символы)
$db->set_charset('utf8mb4');

/**
 * Логирование ошибок.
 */
function logError($message) {
    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/parser_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

$filesProcessed = 0;
$filesSuccess   = 0;
$filesErrors    = 0;

// Проверяем, запущены ли из консоли или через веб
if (php_sapi_name() === 'cli') {
    echo "Запуск парсера в консольном режиме\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
}

// Получаем путь к директории с письмами
$mailsDirRes = $db->query("SELECT value FROM settings WHERE code='directory_mails'");
if ($mailsDirRes && $row = $mailsDirRes->fetch_assoc()) {
    $mailsDir = rtrim($row['value'], '/');
} else {
    // По умолчанию, если не нашли в settings
    $mailsDir = '/mails';
}
$mailsDir = $_SERVER['DOCUMENT_ROOT'] . $mailsDir;

$archiveBaseDirRes = $db->query("SELECT value FROM settings WHERE code='directory_save'");
if ($archiveBaseDirRes && $rowArc = $archiveBaseDirRes->fetch_assoc()) {
    $archiveBaseDir = rtrim($rowArc['value'], '/');
} else {
    $archiveBaseDir = '/archive';
}
$archiveBaseDir = $_SERVER['DOCUMENT_ROOT'] . $archiveBaseDir;

// Получаем все файлы .eml и .msg
$filesEml = glob($mailsDir . '/*.[eE][mM][lL]');
$filesMsg = array_merge(glob($mailsDir . '/*.msg'), glob($mailsDir . '/*.MSG'));
$files = array_merge($filesEml ? $filesEml : [], $filesMsg ? $filesMsg : []);

if (!$files) {
    echo "Нет файлов для обработки\n";
    exit;
}

/**
 * Объединяет переносы строк в заголовках.
 */
function unfoldHeaders($headers) {
    $lines = preg_split('/\R/', $headers);
    $unfolded = '';
    foreach ($lines as $line) {
        if (preg_match('/^\s+/', $line)) {
            // Если строка начинается с пробела или табуляции, добавляем её к предыдущей без лишних пробелов
            $unfolded .= trim($line);
        } else {
            // Новая строка заголовка
            if ($unfolded !== '') {
                $unfolded .= "\n";
            }
            $unfolded .= trim($line);
        }
    }
    return $unfolded;
}

/**
 * Декодирует заголовок Subject.
 */
function decodeSubject($encodedSubject) {
    $unfolded = unfoldHeaders($encodedSubject);

    // 1. Сначала пробуем imap_mime_header_decode (лучше справляется с MIME)
    if (function_exists('imap_mime_header_decode')) {
        $decodedParts = imap_mime_header_decode($unfolded);
        $decoded = '';
        foreach ($decodedParts as $part) {
            $charset = ($part->charset === 'default' || !$part->charset) ? 'UTF-8' : $part->charset;
            $decoded .= @iconv($charset, 'UTF-8//IGNORE', $part->text);
        }
        if (trim($decoded) !== '') {
            return trim($decoded);
        }
    }

    // 2. Если imap_mime_header_decode не сработал, пробуем mb_decode_mimeheader
    if (function_exists('mb_decode_mimeheader')) {
        $decoded = mb_decode_mimeheader($unfolded);
        if (trim($decoded) !== '') {
            return trim($decoded);
        }
    }

    // 3. Если MIME-декодирование не удалось, проверяем кодировку и преобразуем
    if (function_exists('mb_detect_encoding')) {
        $encodings = ['UTF-8', 'WINDOWS-1251', 'ISO-8859-1', 'KOI8-R', 'CP866'];
        $encoding = mb_detect_encoding($unfolded, $encodings, true);

        if ($encoding) {
            if ($encoding !== 'UTF-8') {
                $decoded = @iconv($encoding, 'UTF-8//IGNORE', $unfolded);
                if ($decoded !== false && trim($decoded) !== '') {
                    return trim($decoded);
                }
            } else {
                // Если строка уже в UTF-8, возвращаем её
                return trim($unfolded);
            }
        }
    }

    // 4. Если ничего не сработало, пробуем преобразовать из WINDOWS-1251 в UTF-8
    $decoded = @iconv('WINDOWS-1251', 'UTF-8//IGNORE', $unfolded);
    if ($decoded !== false && trim($decoded) !== '') {
        return trim($decoded);
    }

    // 5. Если ничего не помогло, логируем и возвращаем как есть
    logError("Не удалось декодировать Subject: '$unfolded'");
    return trim($unfolded); // В крайнем случае возвращаем как есть
}

/**
 * Извлекает дату и временную зону из строки Date.
 * Удаляет содержимое в скобках.
 */
function parseEmailDate($dateStr) {
    // Убираем любые комментарии в скобках
    $dateStr = preg_replace('/\s*\(.*\)$/', '', $dateStr);

    // Ищем смещение часового пояса +XXXX/-XXXX
    $pattern = '/([+-]\d{4})/';
    preg_match($pattern, $dateStr, $matches);
    $timezoneOffset = isset($matches[1]) ? $matches[1] : '+0000';

    // Преобразуем дату
    $dateTime = new DateTime($dateStr);

    return [
        'datetime' => $dateTime,
        'timezone' => $timezoneOffset
    ];
}

/**
 * Извлекает все email-адреса из заданного поля (To, CC, BCC).
 */
function extractRecipients($rawRecipients) {
    $decoded = decodeSubject($rawRecipients);
    preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $decoded, $matches);
    if (!empty($matches[0])) {
        return array_unique(array_map('strtolower', array_map('trim', $matches[0])));
    }
    return [];
}

/**
 * Извлекает email-адрес из поля From.
 */
function extractSender($rawSender) {
    // Если есть что-то в угловых скобках, берём оттуда
    if (preg_match('/<([^>]+)>/', $rawSender, $m)) {
        return strtolower(trim($m[1]));
    }

    // Если угловых скобок нет, пробуем искать все возможные email
    $rawSender = str_replace(["\r", "\n"], ' ', $rawSender);
    $decoded = decodeSubject($rawSender);
    if (preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $decoded, $matches)) {
        return strtolower(trim($matches[0][0]));
    }

    return '';
}

// Загружаем записи из business_emails для определения business_id
$businessEmails = [];
$result = $db->query("SELECT business_id, email FROM business_emails");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $businessEmails[] = $row;
    }
}

/**
 * Определяет business_id по домену из email-адресов.
 */
function determineBusinessId($emails, $businessEmails) {
    foreach ($emails as $email) {
        $parts = explode('@', $email);
        if (count($parts) == 2) {
            $domain = strtolower($parts[1]);
            foreach ($businessEmails as $be) {
                // Сравниваем вхождение домена
                if (strpos($domain, strtolower($be['email'])) !== false) {
                    return $be['business_id'];
                }
            }
        }
    }
    return null;
}

foreach ($files as $file) {
    $filesProcessed++;
    $content = file_get_contents($file);
    if ($content === false) {
        logError("Не удалось прочитать файл: {$file}");
        $filesErrors++;
        continue;
    }

    // Разбиваем на заголовки и тело
    $parts = preg_split("/\R\R/", $content, 2);
    if (count($parts) < 2) {
        logError("Неверный формат письма в файле: {$file}");
        $filesErrors++;
        continue;
    }

    // Заголовки "разворачиваем" (unfold)
    $headers = unfoldHeaders($parts[0]);
    $headersArr = [];
    foreach (explode("\n", $headers) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Если строка начинается с пробела, это продолжение предыдущего заголовка
        if (preg_match('/^\s+/', $line)) {
            end($headersArr);
            $lastKey = key($headersArr);
            $headersArr[$lastKey] .= ' ' . trim($line);
        } else {
            $pos = strpos($line, ':');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            $headersArr[$key] = $value;
        }
    }

    // Парсим дату
    if (!empty($headersArr['Date'])) {
        try {
            $dateInfo = parseEmailDate($headersArr['Date']);
            $dateTime = $dateInfo['datetime']->format('Y-m-d H:i:s');
            $timezone = $dateInfo['timezone'];
        } catch (Exception $e) {
            logError("Ошибка разбора даты в файле {$file}: " . $e->getMessage());
            $filesErrors++;
            continue;
        }
    } else {
        // Если нет поля Date — ставим текущее время
        $dateTime = date('Y-m-d H:i:s');
        $timezone = '+0000';
    }

    // Парсим тему
    $subject = isset($headersArr['Subject']) ? decodeSubject($headersArr['Subject']) : '';
    if (empty($subject) && isset($headersArr['Subject'])) {
        $subject = trim($headersArr['Subject']);
    }

    // Собираем получателей (To, CC, BCC)
    $recipientsArr = [];
    if (!empty($headersArr['To'])) {
        $recipientsArr = extractRecipients($headersArr['To']);
    }
    // Проверяем CC/BCC
    foreach ($headersArr as $key => $value) {
        $lowerKey = strtolower($key);
        if (in_array($lowerKey, ['cc', 'bcc'])) {
            $recipientsArr = array_merge($recipientsArr, extractRecipients($value));
        }
    }
    $recipientsArr = array_unique($recipientsArr);

    if (empty($recipientsArr)) {
        logError("Ошибка: Нет корректных email в полях To/CC/BCC в файле {$file}");
        $filesErrors++;
        continue;
    }

    $recipientsStr = implode(', ', $recipientsArr);

    // Отправитель
    if (!empty($headersArr['From'])) {
        $senderEmail = extractSender($headersArr['From']);
        if (empty($senderEmail)) {
            logError("Ошибка: Отсутствует корректный email в поле From в файле {$file}");
            $filesErrors++;
            continue;
        }
        $sender = $senderEmail;
    } else {
        logError("Ошибка: Поле From отсутствует в файле {$file}");
        $filesErrors++;
        continue;
    }

    // Определяем business_id
    $business_id = determineBusinessId(array_merge([$sender], $recipientsArr), $businessEmails);

    // Сохраняем в БД
    $stmt = $db->prepare("INSERT INTO messages (date_sent, timezone, sender, recipients, subject, file_path, business_id, datetime_create)
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        logError("Ошибка подготовки запроса для файла {$file}: " . $db->error);
        $filesErrors++;
        continue;
    }

    // Создаём директории архива по дате письма
    $year = (new DateTime($dateTime))->format('Y');
    $month = (new DateTime($dateTime))->format('m');
    $day = (new DateTime($dateTime))->format('d');

    $archiveDir = $archiveBaseDir . '/' . $year . '/' . $month . '/' . $day;
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0777, true);
    }

    // В базе сохраняем только относительный путь: "YYYY/MM/DD/filename.ext"
    $relativePath = $year . '/' . $month . '/' . $day . '/' . basename($file);
    $absoluteArchivePath = $archiveDir . '/' . basename($file);

    $stmt->bind_param('ssssssi', $dateTime, $timezone, $sender, $recipientsStr, $subject, $relativePath, $business_id);

    if (!$stmt->execute()) {
        logError("Ошибка при импорте файла {$file}: " . $stmt->error);
        $filesErrors++;
        continue;
    }
    $stmt->close();

    // Перемещаем файл в архив
    if (!rename($file, $absoluteArchivePath)) {
        logError("Не удалось переместить файл {$file} в архив {$absoluteArchivePath}");
    } else {
        echo "Файл успешно обработан: " . basename($file) . "\n";
        $filesSuccess++;
    }
}

// Закрываем соединение
$db->close();

echo "Обработка писем завершена.\n";
echo "Статистика: Всего файлов: {$filesProcessed}, успешно: {$filesSuccess}, ошибок: {$filesErrors}.\n";
logError("Статистика импорта: Всего файлов: {$filesProcessed}, успешно: {$filesSuccess}, ошибок: {$filesErrors}.");