# ZendService\Discogs component

Provides access to the Discogs v2.0 APIs, including

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

In development - not production ready.

## Running tests
- install dependencies using `composer install`
- copy `tests/phpunit.xml.dist` to `tests/phpunit.xml`
- copy `tests/TestConfiguration.php.dist` to `tests/TestConfiguration.php` 
- if you wish to test access to the authenticated parts of the API (e.g. the marketplace):
  - set `TESTS_ZEND_SERVICE_DISCOGS_AUTHENTICATED_ENABLED` to `true` 
  - specify the OAuth consumer key/secret for your app (from https://www.discogs.com/settings/developers)
  - specify an OAuth access key/secret for a user
- run `phpunit -c tests/phpunit.xml`

## OAuth authentication
- register your app on https://www.discogs.com/settings/developers and obtain OAuth consumer key/secret
- ...
