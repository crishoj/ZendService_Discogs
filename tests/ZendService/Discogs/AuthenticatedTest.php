<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;
use ZendService\Discogs\Response as DiscogsResponse;

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

}