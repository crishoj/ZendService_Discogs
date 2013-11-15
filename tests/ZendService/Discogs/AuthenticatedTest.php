<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;

class AuthenticatedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Discogs\Discogs discogs
     */
    protected $discogs;

    public function setUp()
    {
        if (!constant('TESTS_ZEND_SERVICE_DISCOGS_AUTHENTICATED_ENABLED') || TESTS_ZEND_SERVICE_DISCOGS_AUTHENTICATED_ENABLED != true)
            $this->markTestSkipped('Authenticated tests are not enabled');
        if (!defined('TESTS_ZEND_SERVICE_DISCOGS_ONLINE_CONSUMER_KEY') || !defined('TESTS_ZEND_SERVICE_DISCOGS_ONLINE_CONSUMER_SECRET'))
            self::markTestSkipped('Consumer key and secret must be set');
        if (!defined('TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ACCESS_KEY') || !defined('TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ACCESS_SECRET'))
            self::markTestSkipped('Access key and secret must be set');

        $this->discogs = new Discogs\Discogs([
            'accessToken' => [
                'token' => TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ACCESS_KEY,
                'secret' => TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ACCESS_SECRET,
            ],
            'oauthOptions' => [
                'consumerKey' => TESTS_ZEND_SERVICE_DISCOGS_ONLINE_CONSUMER_KEY,
                'consumerSecret' => TESTS_ZEND_SERVICE_DISCOGS_ONLINE_CONSUMER_SECRET,
            ],
        ]);

        $this->httpClientAdapterSocket = new \Zend\Http\Client\Adapter\Socket();
        $this->discogs->getHttpClient()->setAdapter($this->httpClientAdapterSocket);

        // Respect rate limit
        sleep(1);
    }

    public function testAuthorisedWithToken()
    {
        $this->assertTrue($this->discogs->isAuthorised());
    }

    public function testIdentity()
    {
        $identity = $this->discogs->identity();
        $this->assertTrue($identity->isSuccess(), $identity->getError());
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $identity);
        $this->assertInternalType('string', $identity->username);
        $this->assertStringEndsWith($identity->username, $identity->resource_url);
    }

    public function testProfile()
    {
        $identity = $this->discogs->identity();
        $profile = $this->discogs->profile($identity->username);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $profile);
        $this->assertTrue($profile->isSuccess(), $profile->getError());
        $this->assertEquals($identity->id, $profile->id);
        $this->assertEquals($identity->username, $profile->username);
        $this->assertEquals($identity->resource_url, $profile->resource_url);
    }

    public function testInventory() {
        $identity = $this->discogs->identity();
        $inventory = $this->discogs->inventory($identity->username);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $inventory);
        $this->assertTrue($inventory->isSuccess(), $inventory->getRawResponse());
    }

    public function testListingCRUD() {
        $identity = $this->discogs->identity();

        // Create Listing
        $response = $this->discogs->createListing([
            'release_id' => 1024123,
            'condition' => 'Good Plus (G+)',
            'price' => 180.0,
        ]);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $response);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());

        // Update Listing
        $inventory = $this->discogs->inventory($identity->username);
        $numListings = count($inventory->listings);
        $this->assertGreaterThanOrEqual(1, $numListings);
        $listing = $inventory->listings[$numListings-1];
        $this->assertInternalType('integer', $listing->id);
        $this->assertEquals($listing->seller->username, $identity->username);
        $response = $this->discogs->updateListing($listing->id, [
            'condition' => 'Fair (F)',
            'price' => 40.0,
        ]);

        // Read Listing
        $inventory = $this->discogs->inventory($identity->username);
        $numListings = count($inventory->listings);
        $this->assertGreaterThanOrEqual(1, $numListings);
        $listing = $inventory->listings[$numListings-1];
        $this->assertEquals($listing->condition, 'Fair (F)');
        $this->assertEquals($listing->price->value, 40.0);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $response);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());

        // Delete Listing
        $response = $this->discogs->deleteListing($listing->id);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $response);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());
        $inventory = $this->discogs->inventory($identity->username);
        $this->assertLessThan($numListings, count($inventory->listings));
    }
}