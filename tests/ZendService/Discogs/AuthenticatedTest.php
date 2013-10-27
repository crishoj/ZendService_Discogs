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

    public function testGetListings() {
        $identity = $this->discogs->identity();
        $this->assertTrue($identity instanceof DiscogsResponse);
        $this->assertTrue($identity->isSuccess(), $identity->getRawResponse());
        $listings = $this->discogs->getListings($identity->username);
        $this->assertTrue($listings instanceof DiscogsResponse);
        $this->assertTrue($listings->isSuccess(), $listings->getRawResponse());
    }

    public function testGetListingIdsAndNames() {
        $identity = $this->discogs->identity();
        $this->assertTrue($identity instanceof DiscogsResponse);
        $this->assertTrue($identity->isSuccess(), $identity->getRawResponse());
        $listings = $this->discogs->getListings($identity->username);
        $this->assertTrue($listings instanceof DiscogsResponse);
        $this->assertTrue($listings->isSuccess(), $listings->getRawResponse());
        foreach($listings->listings as $listings) {
            $this->assertInternalType('integer', $listings->id);
            $this->assertInternalType('string', $listings->release->description);
            $this->assertEquals($listings->seller->username, $identity->username);
        }
    }

    public function testPostRelease() {
        $response = $this->discogs->createRelease([
            'release_id' => 1024123,
            'condition' => 'Mint (M)',
            'price' => (float)80.0,
        ]);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());
    }

    public function testUpdateRelease() {
        $response = $this->discogs->updateRelease(124993585, [
            'release_id' => 1024123,
            'condition' => 'Fair (F)',
            'price' => (float)40.0,
        ]);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());
    }

    public function testDeleteRelease() {
        $response = $this->discogs->deleteRelease(125354107);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getRawResponse());
    }
}