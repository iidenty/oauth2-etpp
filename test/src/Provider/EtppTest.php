<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;
use PHPUnit\Framework\TestCase;

use function http_build_query;
use function json_encode;
use function sprintf;
use function uniqid;

class EtppTest extends TestCase
{
    use QueryBuilderTrait;

    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new \League\OAuth2\Client\Provider\Etpp(
            [
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            ]
        );
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }


    public function testScopes(): void
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);

        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/token', $uri['path']);
    }

    public function testGetAccessToken(): void
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')
                 ->andReturn('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')
                 ->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')
                 ->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testGithubEnterpriseDomainUrls(): void
    {
        $this->provider->domain = 'http://109.233.170.40:2244';

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')
                 ->times(1)
                 ->andReturn(http_build_query([
                     'access_token' => 'mock_access_token',
                     'expires' => 3600,
                     'refresh_token' => 'mock_refresh_token',
                 ]));
        $response->shouldReceive('getHeader')
                 ->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')
                 ->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $providerAuthorizationUrl = $this->provider->domain . '/authorize';
        $providerAccessTokenUrl = $this->provider->domain . '/token';

        $this->assertEquals($providerAuthorizationUrl, $this->provider->getBaseAuthorizationUrl());
        $this->assertEquals($providerAccessTokenUrl, $this->provider->getBaseAccessTokenUrl([]));
    }

    public function testExceptionThrownWhenErrorObjectReceived(): void
    {
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')
                     ->andReturn(json_encode([
                         'message' => 'Validation Failed',
                         'errors' => [
                             ['resource' => 'Issue', 'field' => 'title', 'code' => 'missing_field'],
                         ],
                     ]));
        $postResponse->shouldReceive('getHeader')
                     ->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')
                     ->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testExceptionThrownWhenOAuthErrorReceived(): void
    {
        $status = 200;
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')
                     ->andReturn(json_encode([
                         "error" => "bad_verification_code",
                         "error_description" => "The code passed is incorrect or expired.",
                         "error_uri" => "https =>//developer.github.com/v3/oauth/#bad-verification-code"
                     ]));
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
