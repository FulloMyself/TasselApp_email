# Email service (lightweight PHP)

This tiny HTTP endpoint accepts a JSON POST and attempts to send an HTML email using PHP's `mail()` function.

Recommended deployment: Render as a separate Web Service (PHP). Configure the environment variables on Render rather than committing secrets.

Required / optional environment variables (set on Render):
- `FROM_EMAIL` (optional): the From: address used when sending emails.
- `PHP_MAIL_KEY` (optional): secret API key the backend will include when calling this endpoint.

Local testing (optional)
1. (Optional) Install `vlucas/phpdotenv` with Composer if you prefer a `.env` file locally:
   ```bash
   composer require vlucas/phpdotenv
   ```
2. Create a `.env` file in this folder with:
   ```env
   PHP_MAIL_KEY=your_test_key
   FROM_EMAIL=no-reply@example.com
   ```
3. Start a local PHP server:
   ```powershell
   php -S 0.0.0.0:8080
   ```
4. Call the endpoint:
   ```bash
   curl -X POST http://localhost:8080/send-email.php \
     -H "Content-Type: application/json" \
     -d '{"to":"you@example.com","subject":"Test","text":"Hi","apiKey":"your_test_key"}'
   ```

Notes:
- `mail()` may not be available or configured on all hosts (Render may require external transactional email services). For production reliability, replace this script with one that uses PHPMailer + SMTP or calls a transactional email API (SendGrid, Mailgun, etc.).
- Do not commit a `.env` with secrets. Use Render's environment variables.
