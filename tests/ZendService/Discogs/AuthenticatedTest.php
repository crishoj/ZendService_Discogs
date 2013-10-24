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

    public function testPostRelease() {
        $release = $this->discogs->release('40');
        $this->assertTrue($release instanceof DiscogsResponse);
        $this->assertTrue($release->isSuccess(), $release->getError());

        $condition = CON_MINT;
        $sleeveCondition = CON_GOOD;
        $price = (double)120; // IN DDK
        $comments = "No Comments, except this";
        $allowOffers = false;
        $status = "For Sale";

        $this->assertInternalType('integer', $release->resp->release->id);
        $this->assertInternalType('array', $this->discogs->getConditions());
        $this->assertInternalType('string', $condition);
        $this->assertInternalType('string', $sleeveCondition);

        $this->assertTrue($this->discogs->isAuthorised());
        $postRelease = [
            'release_id' => $release->resp->release->id,
            'condition' => $condition,
            'sleeve_condition' => $sleeveCondition,
            'price' => $price,
            'comments' => $comments,
            'allow_offers' => $allowOffers,
            'status' => $status,
        ];
        $response = $this->discogs->postRelease($postRelease);
        $this->assertTrue($response instanceof DiscogsResponse);
        $this->assertTrue($response->isSuccess(), $response->getError());
    }

    public function testGetListings() {
        $this->assertTrue($this->discogs->isAuthorised());
        $identity = $this->discogs->identity();
        $this->assertTrue($username = $identity->username == "imusic.dk");
        $listings = $this->discogs->getListings($username);
        //var_dump($listings);
        $this->assertTrue($listings instanceof DiscogsResponse);
        $this->assertTrue($listings->isSuccess(), $listings->getError());
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

}