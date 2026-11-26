<?php

/*
 * Router untuk `php artisan serve` (server bawaan PHP).
 *
 * Server bawaan PHP menyajikan file yang ada di public/ langsung, tanpa
 * membaca .htaccess. Karena itu path upload sensitif harus dipaksa masuk ke
 * index.php agar middleware file.access tetap berjalan, sama seperti di Apache.
 */

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if (preg_match('#^/(?:historical_memorandum/|file_(?:historical_memorandum|lampiran_memo)\.zip$)#i', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';

    require_once $publicPath.'/index.php';

    return;
}

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
