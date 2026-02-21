<?php
// send-email.php - lightweight JSON HTTP endpoint to send emails
// Usage: POST JSON { to, subject, text, html, apiKey }

// Attempt to load phpdotenv if available (optional)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

header('Content-Type: application/json');

$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No input provided']);
    exit;
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$to = $data['to'] ?? null;
$subject = $data['subject'] ?? '(no subject)';
$text = $data['text'] ?? '';
$html = $data['html'] ?? '';
$providedKey = $data['apiKey'] ?? '';

$expectedKey = getenv('PHP_MAIL_KEY') ?: '';
$from = getenv('FROM_EMAIL') ?: ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// If an API key is configured, validate it
if ($expectedKey !== '') {
    if (empty($providedKey) || $providedKey !== $expectedKey) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
}

if (empty($to)) {
    http_response_code(400);
    echo json_encode(['error' => 'Recipient `to` is required']);
    exit;
}

// Build headers
$separator = md5(time());
$eol = "\r\n";
$headers = [];
$headers[] = "From: {$from}";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/html; charset=UTF-8";

$message = $html ?: nl2br(htmlspecialchars($text));

$success = false;
// Use mail() if available
if (function_exists('mail')) {
    $success = @mail($to, $subject, $message, implode($eol, $headers));
}

if ($success) {
    echo json_encode(['ok' => true, 'message' => 'Email sent']);
    exit;
}

// If mail() failed, return error with hint
http_response_code(500);
echo json_encode(['error' => 'Failed to send email (mail() returned false). Consider configuring a proper SMTP mailer or using a transactional email provider.']);
exit;
