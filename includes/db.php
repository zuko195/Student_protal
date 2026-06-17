<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_portal');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#7A3B30;">
        <h2>Database Connection Failed</h2>
        <p>Could not connect to the database. Please check your configuration in <code>includes/db.php</code>.</p>
    </div>');
}

$conn->set_charset('utf8mb4');
