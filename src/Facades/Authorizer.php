<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Sergey Tulaev <odminchek@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Odminchek\OAuth2Server\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * This is the authorizer facade class.
 *
 * @see \Odminchek\OAuth2Server\Authorizer
 *
 * @author Sergey Tulaev <odminchek@yandex.ru>
 */
class Authorizer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'oauth2-server.authorizer';
    }
}
