<?php
// send-email.php - lightweight JSON HTTP endpoint to send emails
// Usage: POST JSON { to, subject, text, html, apiKey }

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

// Get env vars (fallback to defaults)
$expectedKey = getenv('PHP_MAIL_KEY') ?: '';
$from = getenv('FROM_EMAIL') ?: 'noreply@localhost';

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

// Prepare message (prefer HTML if provided)
$message = !empty($html) ? $html : nl2br(htmlspecialchars($text));

// Prepare headers
$eol = "\r\n";
$headers = "From: " . $from . $eol;
$headers .= "MIME-Version: 1.0" . $eol;
$headers .= "Content-Type: text/html; charset=UTF-8" . $eol;

// Send using PHP mail()
$success = @mail($to, $subject, $message, $headers);

if ($success) {
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'Email sent']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'mail() failed']);
}
exit;
