<?php
// send-email.php - Send emails using PHPMailer with SMTP
// Usage: POST JSON { to, subject, text, html, apiKey }

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

header('Content-Type: application/json');

// Get input
$input = file_get_contents('php://input');
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No input provided']);
    exit;
}

$data = json_decode($input, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$to = $data['to'] ?? null;
$subject = $data['subject'] ?? 'Message';
$text = $data['text'] ?? '';
$html = $data['html'] ?? '';
$providedKey = $data['apiKey'] ?? '';

// Get env vars
$expectedKey = getenv('PHP_MAIL_KEY') ?: '';
$fromEmail = getenv('FROM_EMAIL') ?: 'noreply@tasselapp.com';
$fromName = getenv('FROM_NAME') ?: 'Tassel Salon';

// SMTP Configuration (add these to your Render environment variables)
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com'; // or your SMTP provider
$smtpPort = getenv('SMTP_PORT') ?: 587;
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpSecure = getenv('SMTP_SECURE') ?: 'tls'; // tls or ssl

// Validate API key if configured
if (!empty($expectedKey)) {
    if (empty($providedKey) || $providedKey !== $expectedKey) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or missing API key']);
        exit;
    }
}

if (empty($to)) {
    http_response_code(400);
    echo json_encode(['error' => 'Recipient email required']);
    exit;
}

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port       = $smtpPort;
    
    // Recipients
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($to);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = !empty($html) ? $html : nl2br(htmlspecialchars($text));
    $mail->AltBody = $text;
    
    $mail->send();
    
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Email sent successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
exit;