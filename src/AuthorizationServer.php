<?php

namespace Odminchek\OAuth2Server;

use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\TokenType\Bearer;
use League\OAuth2\Server\AuthorizationServer as LeagueAuthorizationServer;
use League\OAuth2\Server\Exception\UnsupportedGrantTypeException;
use League\OAuth2\Server\Exception\InvalidRequestException;

class AuthorizationServer extends LeagueAuthorizationServer
{
    // for JSON incoming
    public function issueAccessToken()
    {
        if( !$body = $this->getRequest()->request->get('body')
            OR !$request = json_decode( $body, TRUE )
            OR !isset( $request[ 'grant_type' ] )
            OR !$grantType = $request[ 'grant_type' ]
            OR is_null( $grantType )
            ):
            throw new InvalidRequestException( 'grant_type' );
        endif;

        if ( !in_array( $grantType, array_keys( $this->grantTypes ) ) ):
            throw new UnsupportedGrantTypeException( $grantType );
        endif;

        return $this->getGrantType( $grantType )->completeFlow();
    }
}
