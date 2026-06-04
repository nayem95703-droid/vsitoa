<?php
// রিকোয়েস্টের ইউআরএল থেকে সাব-ফোল্ডার পরিষ্কার করা
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/vsitoa', '', $request_uri);
$_SERVER['REQUEST_URI'] = $request_uri;

// আপনার মেইন প্রজেক্টের রুট ফাইল বা কোর রাউটারকে কল করা
if (file_exists(__DIR__ . '/../public/index.php')) {
    require __DIR__ . '/../public/index.php';
} elseif (file_exists(__DIR__ . '/../index.php')) {
    require __DIR__ . '/../index.php';
}