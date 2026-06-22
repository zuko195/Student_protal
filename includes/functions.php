<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', 'assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_MIME', ['image/jpeg', 'image/png']);

// ── SMTP / email settings ────────────────────────────────────
// Set SMTP_USE_SMTP to true to send email through Gmail or another SMTP server.
define('SMTP_USE_SMTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURITY', 'tls'); // tls, ssl, or none
define('SMTP_USERNAME', 'passgener476@gmail.com');
define('SMTP_PASSWORD', 'jipi vksj wzzt kijh');
define('SMTP_FROM_EMAIL', 'passgener476@gmail.com');
define('SMTP_FROM_NAME', 'Student Management System');
define('SMTP_DEBUG', true);

// ── Session validation ───────────────────────────────────────
function isLoggedIn(): bool {
    if (empty($_SESSION['user_id']) || empty($_SESSION['login_time'])) {
        return false;
    }
    if ((time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        session_destroy();
        return false;
    }
    $_SESSION['login_time'] = time(); // sliding expiry — reset on each page visit
    return true;
}

// ── Require admin ────────────────────────────────────────────
function requireAdmin(): void {
    if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . getBaseUrl() . 'index.php?error=unauthorized');
        exit;
    }
}

// ── Require student ──────────────────────────────────────────
function requireStudent(): void {
    if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'student') {
        header('Location: ' . getBaseUrl() . 'index.php?error=unauthorized');
        exit;
    }
}

// ── Base URL helper ──────────────────────────────────────────
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    // Walk up to project root (handles subdirectories like /admin/, /student/)
    $parts    = explode('/', trim($script, '/'));
    $base     = '';
    // Find the root by detecting known sub-folders
    $subFolders = ['admin', 'student', 'documentation'];
    $filtered = [];
    foreach ($parts as $p) {
        if (in_array($p, $subFolders)) break;
        if ($p !== '') $filtered[] = $p;
    }
    $path = count($filtered) ? '/' . implode('/', $filtered) . '/' : '/';
    return $protocol . '://' . $host . $path;
}

// ── Sanitise output ──────────────────────────────────────────
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ── Validate image upload ────────────────────────────────────
function validateImage(array $file): array {
    $errors = [];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Image is required.';
        return $errors;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Image upload failed. Please try again.';
        return $errors;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Image exceeds the maximum allowed size of 5 MB.';
    }
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Only jpg, jpeg, and png files are allowed.';
    } elseif (!in_array($mime, ALLOWED_MIME)) {
        $errors[] = 'Invalid image file. Only jpg, jpeg, and png are accepted.';
    }
    return $errors;
}

// ── Upload image ─────────────────────────────────────────────
function uploadImage(array $file): string|false {
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'student_' . time() . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return false;
}

// ── Delete image file ────────────────────────────────────────
function deleteImage(string $filename): void {
    if ($filename === '') return;
    $path = UPLOAD_DIR . $filename;
    if (file_exists($path)) {
        unlink($path);
    }
}

// ── Redirect helper ──────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}


// ── Allowed email domains ────────────────────────────────────
define('ALLOWED_EMAIL_DOMAINS', ['gmail.com', 'rayblaze.com']);

function getEmailDomain(string $email): string {
    $at = strrpos($email, '@');
    if ($at === false) {
        return '';
    }
    return strtolower(substr($email, $at + 1));
}

function isAllowedEmailDomain(string $email): bool {
    return in_array(getEmailDomain($email), ALLOWED_EMAIL_DOMAINS, true);
}

// ── Send password reset email ────────────────────────────────
function sendResetEmail(string $toEmail, string $toName, string $token): bool {
    $resetUrl = getBaseUrl() . 'password_reset_confirm.php?token=' . urlencode($token);
    $subject  = 'Password Reset — Student Management System';
    $body     = "Hi " . $toName . ",

"
              . "You requested a password reset for your Student Management System account.

"
              . "Click the link below to reset your password. This link expires in 30 minutes.

"
              . $resetUrl . "

"
              . "If you did not request this, you can safely ignore this email.

"
              . "Regards,
Student Management System";

    if (SMTP_USE_SMTP) {
        return sendSmtpEmail($toEmail, $subject, $body, SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    }

    $headers  = "From: " . SMTP_FROM_EMAIL . "\r\n"
              . "Reply-To: " . SMTP_FROM_EMAIL . "\r\n"
              . "X-Mailer: PHP/" . phpversion();

    $sent = @mail($toEmail, $subject, $body, $headers);
    if (!$sent) {
        error_log('Password reset email failed to send to ' . $toEmail);
    }
    return $sent;
}

function smtpReadResponse($socket): string {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (SMTP_DEBUG) {
            error_log('SMTP <<< ' . trim($line));
        }
        if (preg_match('/^[0-9]{3} /', $line)) {
            break;
        }
    }
    return trim($response);
}

function smtpSendCommand($socket, string $command, string $expected): string {
    if (SMTP_DEBUG) {
        error_log('SMTP >>> ' . $command);
    }
    fwrite($socket, $command . "\r\n");
    $response = smtpReadResponse($socket);
    if (strpos($response, $expected) !== 0) {
        error_log("SMTP command failed ($command): " . $response);
    }
    return $response;
}

function sendSmtpEmail(string $toEmail, string $subject, string $body, string $fromEmail, string $fromName): bool {
    $protocol = SMTP_SECURITY === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = stream_socket_client($protocol . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 30, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        error_log("SMTP connection failed: $errno - $errstr");
        return false;
    }

    $response = smtpReadResponse($socket);
    if (strpos($response, '220') !== 0) {
        error_log('SMTP server did not respond with 220: ' . $response);
        fclose($socket);
        return false;
    }

    smtpSendCommand($socket, "EHLO " . gethostname(), '250');

    if (SMTP_SECURITY === 'tls') {
        $response = smtpSendCommand($socket, 'STARTTLS', '220');
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            return false;
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        smtpSendCommand($socket, "EHLO " . gethostname(), '250');
    }

    $response = smtpSendCommand($socket, 'AUTH LOGIN', '334');
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $response = smtpSendCommand($socket, base64_encode(SMTP_USERNAME), '334');
    if (strpos($response, '334') !== 0) {
        fclose($socket);
        return false;
    }

    $response = smtpSendCommand($socket, base64_encode(SMTP_PASSWORD), '235');
    if (strpos($response, '235') !== 0) {
        fclose($socket);
        return false;
    }

    $response = smtpSendCommand($socket, "MAIL FROM:<" . $fromEmail . '>', '250');
    if (strpos($response, '250') !== 0) {
        fclose($socket);
        return false;
    }

    $response = smtpSendCommand($socket, "RCPT TO:<" . $toEmail . '>', '250');
    if (strpos($response, '250') !== 0 && strpos($response, '251') !== 0) {
        fclose($socket);
        return false;
    }

    $response = smtpSendCommand($socket, 'DATA', '354');
    if (strpos($response, '354') !== 0) {
        fclose($socket);
        return false;
    }

    $headers  = "From: " . $fromName . " <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "Content-Transfer-Encoding: 7bit\r\n";

    $message = "Subject: " . $subject . "\r\n" . $headers . "\r\n" . $body . "\r\n.\r\n";
    fwrite($socket, $message);
    $response = smtpReadResponse($socket);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return strpos($response, '250') === 0;
}

// ── Flash message ─────────────────────────────────────────────
function setFlash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}
