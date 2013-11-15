# ZendService\Discogs component

PHP wrapper for the Discogs v2.0 API, heavily based on the ZendService\Twitter component. Provides access to the following parts of the Discogs API:

- Database
  - Artist
  - Release
  - Master
  - Label
  - Image
  - Search
- Marketplace
  - Inventory
  - Listing
  - Order
  - Fee
  - Price Suggestions
- User
  - Identity
  - Profile
  - Collection
  - Wantlist

Marketplace and User parts of the API is supported with OAuth authentication.

## Status

Works, but has some missing parts and rough edges.

## Running tests
- install dependencies using `composer install`
- copy `tests/phpunit.xml.dist` to `tests/phpunit.xml`
- copy `tests/TestConfiguration.php.dist` to `tests/TestConfiguration.php` 
- if you wish to test access to the authenticated parts of the API (e.g. the marketplace):
  - set `TESTS_ZEND_SERVICE_DISCOGS_AUTHENTICATED_ENABLED` to `true` 
  - specify the OAuth consumer key/secret for your app (from https://www.discogs.com/settings/developers)
  - specify an OAuth access key/secret for a user
- run `phpunit -c tests/phpunit.xml`

## Usage
```
$this->discogs = new Discogs\Discogs();
$label = $this->discogs->label(1);
printf("Read more about label %s on these websites: %s\n", $label->name, implode(', ', $label->urls));
```

## OAuth authentication
Register your app on https://www.discogs.com/settings/developers and obtain OAuth consumer key/secret.

Pass the credentials to the service constructor: 
```
$discogs = new Discogs\Discogs([
    'oauthOptions' => [
        'consumerKey' => [...],
        'consumerSecret' => [...],
        'callbackUrl'    => [...], 
    ],
]);
```

Obtain request token and keep it (serialize in session or otherwise):
```
$requestToken = $discogs->getRequestToken();
```

Redirect user to Discogs to authorize your app's access:
```
$url = $discogs->getRedirectUrl();
// Redirect
```

When user returns to your callback URL, use the verifier from the request parameters and the
request token from before to obtain an access token:
```
$accessToken = $consumer->getAccessToken([
    'oauth_token'    => $requestToken->getToken(),
    'oauth_verifier' => $verifier,
], $requestToken);
```

You can throw away the request token, but should keep the newly obtained access key/secret:
```
if ($accessToken->isValid()) {
    printf("Authorized successfully\n");
    printf("Access key: %s\n", $accessToken->getToken());
    printf("Access secret: %s\n", $accessToken->getTokenSecret());
} else {
    printf("Something went wrong: %s\n", $accessToken->getResponse()->getReasonPhrase());
}
```

While you have a valid access token, instantiate an authenticated client like this:
```
$discogs = new Discogs\Discogs([
    'accessToken' => [
        'token' => [...],
        'secret' => [...],
    ],
    'oauthOptions' => [
        'consumerKey' => [...],
        'consumerSecret' => [...],
    ],
]);
```

Using the authenticated client, you can do e.g.:
```
$identity = $discogs->identity();
printf("Hello, %s\n", $identity->username);
```
