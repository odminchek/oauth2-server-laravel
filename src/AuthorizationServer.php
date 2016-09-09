<?php

namespace Odminchek\OAuth2Server;

use League\OAuth2\Server\AuthorizationServer as LeagueAuthorizationServer;
use League\OAuth2\Server\TokenType\Bearer;
use Odminchek\OAuth2Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\Exception;

class AuthorizationServer extends LeagueAuthorizationServer
{
    protected $grantTypes = [/* 'password' */];

    public function __construct()
    {
        // Set Bearer as the default token type
        $this->setTokenType( new Bearer() );

        parent::__construct();

        return $this;
    }

    // public function addGrantType( GrantTypeInterface $grantType, $identifier = NULL )
    // {
    //     if( is_null( $identifier ) ):
    //         $identifier = $grantType->getIdentifier();
    //     endif;

    //     // Inject server into grant
    //     $grantType->setAuthorizationServer( $this );

    //     $this->grantTypes[ $identifier ] = $grantType;

    //     if ( !is_null( $grantType->getResponseType() ) ):
    //         $this->responseTypes[] = $grantType->getResponseType();
    //     endif;

    //     return $this;
    // }

    public function issueAccessToken()
    {
        // получаем grant_type из JSON
        $grantType = NULL;
        if( !$body = json_decode( $this->getRequest()->request->get( 'body' ), TRUE)
            OR !isset( $body[ 'grant_type' ] )
            OR !$grantType = $body[ 'grant_type' ]
            OR is_null( $grantType )
            ):
            throw new Exception\InvalidRequestException( 'grant_type' );
        endif;

        // old code
        // $grantType = $this->getRequest()->request->get('grant_type');

        // if ( is_null( $grantType ) ):
        //     throw new Exception\InvalidRequestException( 'grant_type' );
        // endif;


        // Ensure grant type is one that is recognised and is enabled
        if ( !in_array( $grantType, array_keys( $this->grantTypes ) ) ):
            throw new Exception\UnsupportedGrantTypeException( $grantType );
        endif;

        // Complete the flow
        return $this->getGrantType( $grantType )->completeFlow();
    }

    public function getGrantType( $grantType )
    {
        if ( isset( $this->grantTypes[ $grantType] ) ) {
            return $this->grantTypes[ $grantType ];
        }

        throw new Exception\InvalidGrantException($grantType);
    }
}
