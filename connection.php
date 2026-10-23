<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'sessions';

    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0777, true);
    }

    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

const STORE_DB_HOST = 'localhost';
const STORE_DB_USER = 'root';
const STORE_DB_PASS = '';
const STORE_DB_NAME = 'la-imparial';
const STORE_SITE_URL = 'http://localhost/la-imapiral';
const STORE_ADMIN_EMAIL = 'simpleu024@gmail.com';
const STORE_NOTIFY_EMAIL = 'simpleu024@gmail.com';
const STORE_MAIL_FROM = 'simpleu024@gmail.com';
const STORE_MAIL_FROM_NAME = 'La Imparial';
const STORE_SMTP_HOST = 'smtp.gmail.com';
const STORE_SMTP_PORT = 465;
const STORE_SMTP_USERNAME = 'simpleu024@gmail.com';
const STORE_SMTP_PASSWORD = 'hglkaiwfjagdasbw';
const STORE_SMTP_ENCRYPTION = 'ssl';
// Roman Urdu: Google Cloud Console se Client ID yahan paste karni hai.
const STORE_GOOGLE_CLIENT_ID = '373058531914-2082u07rfonqgbh3esv424sq67o8uper.apps.googleusercontent.com';
// Roman Urdu: Google Cloud Console se Client Secret yahan paste karna hai.
const STORE_GOOGLE_CLIENT_SECRET = '';
// Roman Urdu: callback.php ka exact localhost URL Google Console ke redirect URI se match hona chahiye.
const STORE_GOOGLE_REDIRECT = STORE_SITE_URL . '/callback.php';
const STORE_ADMIN_PASSWORD = 'simpledimple';

$con = @mysqli_connect(STORE_DB_HOST, STORE_DB_USER, STORE_DB_PASS);

if ($con instanceof mysqli) {
    @mysqli_query($con, 'CREATE DATABASE IF NOT EXISTS `' . STORE_DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    @mysqli_select_db($con, STORE_DB_NAME);
    @mysqli_set_charset($con, 'utf8mb4');
}
