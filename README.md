# Etpp Provider for OAuth 2.0 Client
This package provides Etpp OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require da3nil/oauth2-etpp
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth2\Client\Provider\Etpp` as the provider.

### Authorization Code Flow

```php
$provider = new League\OAuth2\Client\Provider\Etpp([
    'clientId'          => '{etpp-client-id}',
    'clientSecret'      => '{etpp-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getNickname());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Etpp authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['user','user:email','repo'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://developer.github.com/v3/oauth/#scopes).

- user
- user:email
- user:follow
- public_repo
- repo
- repo_deployment
- repo:status
- delete_repo
- notifications
- gist
- read:repo_hook
- write:repo_hook
- admin:repo_hook
- admin:org_hook
- read:org
- write:org
- admin:org
- read:public_key
- write:public_key
- admin:public_key

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/da3nil/oauth2-etpp/blob/master/LICENSE) for more information.
