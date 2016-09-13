<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Sergey Tulaev <odminchek@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Odminchek\OAuth2Server\Middleware;

use Closure;
use League\OAuth2\Server\Exception\AccessDeniedException;
use Odminchek\OAuth2Server\Authorizer;

use App\OauthSessionsModel;
use App\OauthAccessTokensModel;
use App\UserModel;

/**
 * This is the oauth user middleware class.
 *
 * @author Vincent Klaiber <hello@vinkla.com>
 */
class OAuthUserOwnerMiddleware
{
    /**
     * The Authorizer instance.
     *
     * @var \Odminchek\OAuth2Server\Authorizer
     */
    protected $authorizer;

    /**
     * Create a new oauth user middleware instance.
     *
     * @param \Odminchek\OAuth2Server\Authorizer $authorizer
     */
    public function __construct(Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @throws \League\OAuth2\Server\Exception\AccessDeniedException
     *
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        if( !$body = json_decode( $request->get( 'body' ), TRUE )
            OR !is_array( $body )
            OR !count( $body )
            OR !isset( $body[ 'access_token' ] )
            OR !$accessToken = OauthAccessTokensModel::where( 'id', '=', $body[ 'access_token' ] )->first()
            OR !isset( $accessToken->expire_time )
            OR time() > $accessToken->expire_time
            OR !isset( $accessToken->session_id )
            OR !$session = OauthSessionsModel::find( $accessToken->session_id )
            OR !isset( $session->owner_type )
            OR $session->owner_type !== 'user'
            OR !isset( $session->owner_id )
            OR !ctype_xdigit( $session->owner_id )
            OR !$user = UserModel::find( $session->owner_id )
            ):
            throw new AccessDeniedException();
        endif;

        return $next( $request );
    }
}
