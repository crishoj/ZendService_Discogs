<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;
use ZendService\Discogs\Response as DiscogsResponse;
require_once 'tests/TestConfiguration.php';
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
        $this->assertInternalType('string', $identity->username);
        $this->assertStringEndsWith($identity->username, $identity->resource_url);
    }

    public function testProfile()
    {
        $identity = $this->discogs->identity();
        $profile = $this->discogs->profile($identity->username);
        $this->assertTrue($profile->isSuccess(), $profile->getError());
        $this->assertEquals($identity->id, $profile->id);
        $this->assertEquals($identity->username, $profile->username);
        $this->assertEquals($identity->resource_url, $profile->resource_url);
    }

    public function testGetInventoryIdsAndNames() {
        $identity = $this->discogs->identity();
        $listings = $this->discogs->getInventory($identity->username);
        $this->assertTrue($listings instanceof DiscogsResponse);
        $this->assertTrue($listings->isSuccess(), $listings->getRawResponse());
        foreach($listings->listings as $listing) {
            $this->assertInternalType('integer', $listing->id);
            $this->assertInternalType('string', $listing->release->description);
            $this->assertEquals($listing->seller->username, $identity->username);
        }
    }

    public function testListingCRUD() {
        //Create Listing
        $response = $this->discogs->createListing([
            'release_id' => 1024123,
            'condition' => 'Mint (M)',
            'price' => 80.0,
        ]);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());

        //Update Listing
        $identity = $this->discogs->identity();
        $listings = $this->discogs->getInventoryIdsAndNames($identity->username);
        $this->assertInternalType('integer', $listings[0]['id']);
        $this->assertInternalType('string', $listings[0]['description']);
        $response = $this->discogs->updateListing($listings[0]['id'], [
            'release_id' => 1024123,
            'condition' => 'Fair (F)',
            'price' => (float)40.0,
        ]);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());

        //Delete Listing
        $response = $this->discogs->deleteListing($listings[0]['id']);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());
    }
}