<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Cookie JWT untuk link file dibuat dari route api (tanpa EncryptCookies),
     * jadi jangan coba didekripsi saat request masuk lewat group web.
     */
    public function isDisabled($name)
    {
        return $name === config('file_access.cookie') || parent::isDisabled($name);
    }
}
