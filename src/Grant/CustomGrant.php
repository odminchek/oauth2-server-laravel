<?php

namespace Odminchek\OAuth2Server\Grant;

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Event;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Util\SecureKey;

// added by me
use League\OAuth2\Server\Grant\AbstractGrant;

/**
 * Password grant class
 */
class CustomGrant extends AbstractGrant
{
    /**
     * Grant identifier
     *
     * @var string
     */
    protected $identifier = 'custom';

    /**
     * Response type
     *
     * @var string
     */
    protected $responseType;

    /**
     * Callback to authenticate a user's name and password
     *
     * @var callable
     */
    protected $callback;

    /**
     * Access token expires in override
     *
     * @var int
     */
    protected $accessTokenTTL;

    /**
     * Set the callback to verify a user's username and password
     *
     * @param callable $callback The callback function
     *
     * @return void
     */
    public function setVerifyCredentialsCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Return the callback function
     *
     * @return callable
     *
     * @throws
     */
    protected function getVerifyCredentialsCallback()
    {
        if (is_null($this->callback) || !is_callable($this->callback)) {
            throw new Exception\ServerErrorException('Null or non-callable callback set on Password grant');
        }

        return $this->callback;
    }

    /**
     * Complete the password grant
     *
     * @return array
     *
     * @throws
     */
    public function completeFlow()
    {
        // get request
        $body = $this->server->getRequest()->request->get( 'body' );
        $request = json_decode( $body, TRUE );

        if( !isset( $request[ 'client_id' ] )
            OR !$clientId = $request[ 'client_id' ] 
            OR is_null( $clientId )
            ):
            throw new Exception\InvalidRequestException('client_id');
        endif;

        if( !isset( $request[ 'client_secret' ] )
            OR !$clientSecret = $request[ 'client_secret' ] 
            OR is_null( $clientSecret )
            ):
            throw new Exception\InvalidRequestException('client_secret');
        endif;

        $client = $this->server->getClientStorage()->get(
            $clientId,
            $clientSecret,
            null,
            $this->getIdentifier()
        );

        if( ( $client instanceof ClientEntity ) === FALSE ):
            $this->server->getEventEmitter()->emit( new Event\ClientAuthenticationFailedEvent( $this->server->getRequest() ) );
            throw new Exception\InvalidClientException();
        endif;

        if( !isset( $request[ 'username' ] )
            OR !$username = $request[ 'username' ]
            OR is_null( $username )
            ):
            throw new Exception\InvalidRequestException('username');
        endif;

        if( !isset( $request[ 'password' ] )
            OR !$password = $request[ 'password' ]
            OR is_null( $password )
            ):
            throw new Exception\InvalidRequestException('password');
        endif;

        $userId = call_user_func( $this->getVerifyCredentialsCallback(), $username, $password );

        if( $userId === FALSE ):
            $this->server->getEventEmitter()->emit( new Event\UserAuthenticationFailedEvent( $this->server->getRequest() ) );
            throw new Exception\InvalidCredentialsException();
        endif;

        // Validate any scopes that are in the request
        $scopeParam = $this->server->getRequest()->request->get( 'scope', '' );
        $scopes = $this->validateScopes( $scopeParam, $client );

        // Create a new session
        $session = new SessionEntity( $this->server );
        $session->setOwner( 'user', $userId );
        $session->associateClient( $client );

        // Generate an access token
        $accessToken = new AccessTokenEntity( $this->server );
        $accessToken->setId( SecureKey::generate() );
        $accessToken->setExpireTime( $this->getAccessTokenTTL() + time() );

        // Associate scopes with the session and access token
        foreach( $scopes as $scope ):
            $session->associateScope( $scope );
        endforeach;

        foreach ( $session->getScopes() as $scope ):
            $accessToken->associateScope( $scope );
        endforeach;

        $this->server->getTokenType()->setSession( $session );
        $this->server->getTokenType()->setParam( 'access_token', $accessToken->getId() );
        $this->server->getTokenType()->setParam( 'expires_in', $this->getAccessTokenTTL() );

        // Associate a refresh token if set
        if ($this->server->hasGrantType( 'refresh_token' ) ):
            $refreshToken = new RefreshTokenEntity( $this->server );
            $refreshToken->setId( SecureKey::generate() );
            $refreshToken->setExpireTime( $this->server->getGrantType( 'refresh_token' )->getRefreshTokenTTL() + time() );
            $this->server->getTokenType()->setParam( 'refresh_token', $refreshToken->getId() );
        endif;

        // Save everything
        $session->save();
        $accessToken->setSession( $session );
        $accessToken->save();

        if ($this->server->hasGrantType( 'refresh_token' ) ) {
            $refreshToken->setAccessToken( $accessToken );
            $refreshToken->save();
        }

        return $this->server->getTokenType()->generateResponse();
    }
}
