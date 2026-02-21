<?php
// send-email.php - JSON POST endpoint to send emails
// Usage: POST JSON { to, subject, text, html, apiKey }

// Attempt to load Composer autoload + dotenv
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\\Dotenv::createImmutable(__DIR__);
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

$message = $html ?: nl2br(htmlspecialchars($text));

// Prefer SMTP via PHPMailer when configured
$smtpHost = getenv('SMTP_HOST') ?: '';
$useSmtp = !empty($smtpHost);

if ($useSmtp && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ?: '';
        $mail->Password = getenv('SMTP_PASS') ?: '';
        $smtpSecure = getenv('SMTP_SECURE') ?: '';
        if (!empty($smtpSecure)) $mail->SMTPSecure = $smtpSecure; // 'ssl' or 'tls'
        $mail->Port = intval(getenv('SMTP_PORT') ?: 587);

        $mail->setFrom($from);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $text;

        $mail->send();
        echo json_encode(['ok' => true, 'message' => 'Email sent via SMTP']);
        exit;
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        // fall through to try mail()
    }
}

// Fallback to mail()
if (function_exists('mail')) {
    $eol = "\r\n";
    $headers = [];
    $headers[] = "From: {$from}";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $success = @mail($to, $subject, $message, implode($eol, $headers));
    if ($success) {
        echo json_encode(['ok' => true, 'message' => 'Email sent via mail()']);
        exit;
    }
}

http_response_code(500);
echo json_encode(['error' => 'Failed to send email. Configure SMTP or use a transactional email provider.']);
exit;
