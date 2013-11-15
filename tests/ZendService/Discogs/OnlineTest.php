<?php

namespace ZendTest\Discogs;

use Zend\Http;
use ZendService\Discogs;

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
    }

    protected function tearDown()
    {
        // Respect rate limit
        sleep(1);
    }

    public function testNotAuthorisedWithoutToken()
    {
        $this->assertFalse($this->discogs->isAuthorised());
    }

    public function testLabel()
    {
        $id = 1;
        $label = $this->discogs->label($id);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $label);
        $this->assertTrue($label->isSuccess(), $label->getError());
        $this->assertEquals($id, $label->id);
        $this->assertInternalType('string', $label->name);
        $this->assertInternalType('array', $label->urls);
    }

    public function testSearch()
    {
        $result = $this->discogs->search('Planet E');
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $result);
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

    public function testSearchResultsIteration()
    {
        $results = $this->discogs->search('Kompakt');
        $this->assertTrue($results->isSuccess(), $results->getError());
        foreach ($results as $result) {
            $this->assertInternalType('string', $result->type);
            $this->assertInternalType('string', $result->uri);
        }
    }

    public function testSearchResultsArrayAccess()
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

    public function testParameterSearch()
    {
        $barcode = '4 526180 117285';
        $result = $this->discogs->search('', ['barcode' => $barcode]);
        $this->assertTrue($result->isSuccess(), $result->getError());

        $this->assertGreaterThanOrEqual(1, $result->items);
        $this->assertContains($barcode, $result[0]->barcode); // FIXME: should compare normalized barcodes
        $this->assertContains('Black Jazz Records', $result[0]->label);
    }

    public function testRelease()
    {
        $id = 23;
        $release = $this->discogs->release($id);
        $this->assertInstanceOf('ZendService\\Discogs\\Response', $release);
        $this->assertTrue($release->isSuccess(), $release->getError());
        $this->assertEquals($id, $release->id);
    }

}