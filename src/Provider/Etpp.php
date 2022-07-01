<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\EtppIdentityProviderException;
use League\OAuth2\Client\Provider\Exception\NotImplementedException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Etpp extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Domain
     *
     * @var string
     */
    public $domain = 'http://109.233.170.40:2244';

    /**
     * Api domain
     *
     * @var string
     */
    public $apiDomain = 'http://109.233.170.40:2244/api';

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        throw new NotImplementedException('User is not supported');
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://developer.github.com/v3/#client-errors
     * @link   https://developer.github.com/v3/oauth/#common-errors-for-the-access-token-request
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array             $data     Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw EtppIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw EtppIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param  array       $response
     * @param  AccessToken $token
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        throw new NotImplementedException('User is not supported');
    }
}