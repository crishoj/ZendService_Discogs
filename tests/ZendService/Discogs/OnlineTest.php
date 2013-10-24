<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;
use ZendService\Discogs\Response as DiscogsResponse;
require_once 'tests/TestConfiguration.php';

class OnlineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Discogs\Discogs discogs
     */
    protected $discogs;

    public function setUp()
    {
        if (!constant('TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ENABLED') || TESTS_ZEND_SERVICE_DISCOGS_ONLINE_ENABLED != true)
            $this->markTestSkipped('Online tests are not enabled');

        $this->discogs = new Discogs\Discogs();
        $this->httpClientAdapterSocket = new \Zend\Http\Client\Adapter\Socket();
        $this->discogs->getHttpClient()->setAdapter($this->httpClientAdapterSocket);

        // Respect rate limit
        sleep(1);
    }

    public function testNotAuthorisedWithoutToken()
    {
        $this->assertFalse($this->discogs->isAuthorised());
    }

    public function testLabel()
    {
        $label = $this->discogs->label(1);
        $this->assertTrue($label->isSuccess(), $label->getError());
        $this->assertInternalType('string', $label->name);
        $this->assertInternalType('array', $label->urls);
    }

    public function testSearch()
    {
        $result = $this->discogs->search('Planet E');
        $this->assertTrue($result->isSuccess(), $result->getError());
        $this->assertInternalType('array', $result->results);
    }

    public function testSearchPaginationAttributes()
    {
        $results = $this->discogs->search('Sundaze');
        $this->assertTrue($results->isSuccess(), $results->getError());

        $this->assertInternalType('integer', $results->page);
        $this->assertInternalType('integer', $results->pages);
        $this->assertInternalType('integer', $results->items);

        $this->assertNotEmpty($results->pagination);
        $this->assertEquals($results->page, $results->pagination->page);
        $this->assertEquals($results->pages, $results->pagination->pages);
        $this->assertEquals($results->items, $results->pagination->items);
    }

    public function testSearchresultsIteration()
    {
        $results = $this->discogs->search('Kompakt');
        $this->assertTrue($results->isSuccess(), $results->getError());
        foreach ($results as $result) {
            $this->assertInternalType('string', $result->type);
            $this->assertInternalType('string', $result->uri);
        }
    }

    public function testSearchresultsArrayAccess()
    {
        $results = $this->discogs->search('Kompakt');
        $this->assertTrue($results->isSuccess(), $results->getError());
        $this->assertGreaterThan(0, $results->items);
        $midIdx = ceil(min($results->items, $results->per_page) / 2);
        $result = $results[$midIdx];
        $this->assertNotEmpty($result);
        $this->assertInternalType('string', $result->type);
        $this->assertInternalType('string', $result->uri);
    }

    public function testRelease()
    {
        $id = '23';
        $release = $this->discogs->release($id);
        $this->assertTrue($release instanceof DiscogsResponse);
        $this->assertTrue($release->isSuccess(), $release->getError());
        $this->assertEquals($id, $release->resp->release->id);
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

        $this->assertTrue($this->discogs->isAuthorised());
        $profile = $this->discogs->profile($identity->username);
        $this->assertTrue($profile->isSuccess(), $profile->getError());

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

        $identity = $this->discogs->identity();
        $this->assertTrue($username = $identity->username == "imusic.dk");
        $listings = $this->discogs->getListings($username);
        var_dump($listings);

    }

    public function testSearchLabels()
    {
        $results = $this->discogs->searchLabels('Kompakt');
        $this->assertTrue($results->isSuccess(), $results->getError());
        $this->assertGreaterThan(0, $results->items);
        foreach ($results as $result) {
            $this->assertEquals('label', $result->type);
            $this->assertInternalType('string', $result->title);
        }
    }



}