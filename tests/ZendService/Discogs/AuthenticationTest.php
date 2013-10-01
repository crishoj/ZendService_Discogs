<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;
use ZendService\Discogs\Response as DiscogsResponse;

class AuthenticationTest extends \PHPUnit_Framework_TestCase
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

        $this->discogs = new Discogs\Discogs([
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

    public function testRequestToken()
    {
        $requestToken = $this->discogs->getRequestToken();
        $this->assertInstanceOf('\\ZendOAuth\\Token\\Request', $requestToken);
        $url = $this->discogs->getRedirectUrl();
        $this->assertInternalType('string', $url);
        $this->assertRegExp('/oauth_token=.{10,}/', $url);
    }

}