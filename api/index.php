<?php
// v4 — force fresh Vercel build cache
// রিকোয়েস্টের ইউআরএল থেকে সাব-ফোল্ডার পরিষ্কার করা
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/vsitoa', '', $request_uri);
$_SERVER['REQUEST_URI'] = $request_uri;

// আপনার মেইন ফোল্ডারের আসল index.php ফাইলকে কল করা
require __DIR__ . '/../index.php';