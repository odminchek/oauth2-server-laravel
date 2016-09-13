<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Sergey Tulaev <odminchek@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Odminchek\OAuth2Server\Storage;

use Carbon\Carbon;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;

use App\OauthAccessTokensModel;
use App\OauthAccessTokenScopesModel;

/**
 * This is the fluent access token class.
 *
 * @author Sergey Tulaev <odminchek@yandex.ru>
 */
class FluentAccessToken extends AbstractFluentAdapter implements AccessTokenInterface
{
    /**
     * Get an instance of Entities\AccessToken.
     *
     * @param string $token The access token
     *
     * @return null|AbstractTokenEntity
     */
    public function get( $token )
    {
        // mongo
        if( !$accessToken = OauthAccessTokensModel::where( 'id', '=', $token )->first() 
            OR !isset( $accessToken->id )
            OR !isset( $accessToken->expire_time )
            ):
            return FALSE;
        endif;

        $result = new AccessTokenEntity( $this->getServer() );
        $result->setId( $accessToken->id );
        $result->setExpireTime( $accessToken->expire_time );

        return $result;

        // $result = $this->getConnection()->table('oauth_access_tokens')
        //         ->where('oauth_access_tokens.id', $token)
        //         ->first();

        // if ( is_null( $result ) ) {
        //     return;
        // }

        // return ( new AccessTokenEntity( $this->getServer() ) )
        //        ->setId( $result->id )
        //        ->setExpireTime( (int)$result->expire_time );
    }

    /*
    public function getByRefreshToken(RefreshTokenEntity $refreshToken)
    {
        $result = $this->getConnection()->table('oauth_access_tokens')
                ->select('oauth_access_tokens.*')
                ->join('oauth_refresh_tokens', 'oauth_access_tokens.id', '=', 'oauth_refresh_tokens.access_token_id')
                ->where('oauth_refresh_tokens.id', $refreshToken->getId())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setExpireTime((int)$result->expire_time);
    }
    */

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AccessTokenEntity $token)
    {
        // mongo

        // if( !$scopes = OauthAccessTokenScopesModel::where( 'access_token_id', '=', $token->getId() )->get() 
        //     OR !$scopes = $scopes->toArray()
        //     OR !is_array( $scopes )
        //     OR empty( $scopes )
        //     ):
        //     return FALSE;
        // endif;

        // foreach( $scopes as $scope ):
        //     $scopeEntity = new ScopeEntity( $this->getServer() );
        //     $result[] =  $scopeEntity->hydrate( [ 'id' => $scope[ 'id' ], 'description' => $scope[ 'description' ] ] );
        // endforeach;

        // if( !isset( $result )
        //     OR !is_array( $result )
        //     OR empty( $result )
        //     ):
        //     return FALSE;
        // endif;

        // return $result;


        $result = $this->getConnection()->table('oauth_access_token_scopes')
                ->select('oauth_scopes.*')
                ->join('oauth_scopes', 'oauth_access_token_scopes.scope_id', '=', 'oauth_scopes.id')
                ->where('oauth_access_token_scopes.access_token_id', $token->getId())
                ->get();

        $scopes = [];

        foreach ($result as $scope) {
            $scopes[] = (new ScopeEntity($this->getServer()))->hydrate([
               'id' => $scope->id,
                'description' => $scope->description,
            ]);
        }

        return $scopes;
    }

    /**
     * Creates a new access token.
     *
     * @param string $token The access token
     * @param int $expireTime The expire time expressed as a unix timestamp
     * @param string|int $sessionId The session ID
     *
     * @return \League\OAuth2\Server\Entity\AccessTokenEntity
     */
    public function create( $token, $expireTime, $sessionId )
    {
        // mongo
        $accessToken = new OauthAccessTokensModel;

        $accessToken->id = $token;
        $accessToken->expire_time = $expireTime;
        $accessToken->session_id = $sessionId;
        $accessToken->created_at = Carbon::now();
        $accessToken->updated_at = Carbon::now();

        if( !$accessToken->save() ):
            return FALSE;
        endif;

        $result = new AccessTokenEntity( $this->getServer() );
        $result->setId( $accessToken->id );
        $result->setExpireTime( $accessToken->expire_time );

        return $result;
    }

    /**
     * Associate a scope with an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope( AccessTokenEntity $token, ScopeEntity $scope )
    {
        $accessToken = new OauthAccessTokenScopesModel;

        $accessToken->access_token_id = $token->getId();
        $accessToken->scope_id = $scope->getId();
        $accessToken->created_at = Carbon::now();
        $accessToken->updated_at = Carbon::now();

        if( !$accessToken->save() ):
            return FALSE;
        endif;

        return TRUE;

        // $this->getConnection()->table('oauth_access_token_scopes')->insert([
        //     'access_token_id' => $token->getId(),
        //     'scope_id' => $scope->getId(),
        //     'created_at' => Carbon::now(),
        //     'updated_at' => Carbon::now(),
        // ]);
    }

    /**
     * Delete an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token to delete
     *
     * @return void
     */
    public function delete( AccessTokenEntity $token )
    {
        // MongoDB

        if( !$accessToken = OauthAccessTokensModel::where( 'id', '=', $token->getId() )->first()
            OR !$accessToken->delete()
            ):
            return FALSE;
        endif;

        return TRUE;


        // $this->getConnection()->table('oauth_access_tokens')
        // ->where('oauth_access_tokens.id', $token->getId())
        // ->delete();
    }
}
