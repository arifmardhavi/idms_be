<?php

return [
    /*
     * Nama cookie yang menyimpan JWT untuk link file yang dibuka langsung
     * oleh browser (link biasa tidak mengirim header Authorization).
     */
    'cookie' => env('FILE_ACCESS_COOKIE', 'idms_file_token'),
    'cookie_samesite' => env('FILE_ACCESS_COOKIE_SAMESITE', 'lax'),
    'cookie_secure' => env('FILE_ACCESS_COOKIE_SECURE', false),

    /*
     * Username yang selalu memiliki akses (sama seperti daftar di AuthController).
     */
    'superusers' => ['admin', 'superadmin', 'faza.ahmad'],
];
