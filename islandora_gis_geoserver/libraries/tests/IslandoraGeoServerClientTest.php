<?php

  /**
   * @author griffinj@lafayette.edu
   * The unit tests for the IslandoraGeoServer API
   *
   */

require dirname(__DIR__) . '/IslandoraGeoServerClient.php';

// use Guzzle\Tests\Server as Server;
// use GuzzleHttp\Subscriber\History;
use IslandoraGeoServer\IslandoraGeoServerSession as IslandoraGeoServerSession;
use IslandoraGeoServer\IslandoraGeoServerClient as IslandoraGeoServerClient;

class IslandoraGeoServerClientTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->session = new IslandoraGeoServerSession('secret', 'admin');

    $this->plugin = new Guzzle\Plugin\Mock\MockPlugin();

    $guzzle = new Guzzle\Http\Client();
    $guzzle->addSubscriber($this->plugin);
    $this->client = new IslandoraGeoServerClient($this->session, $guzzle);
  }

  public function testGet() {

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $this->client->get('path');

    $requests = $this->plugin->getReceivedRequests();
    $this->assertNotEmpty($requests);

    $request = array_pop($requests);
    $this->assertEquals('GET', $request->getMethod());
    $this->assertEquals('localhost', $request->getHost());
    $this->assertEquals('/geoserver/rest/path', $request->getPath());
  }

  public function testPost() {

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));

    $params = array('param' => 'value');
    $this->client->post('path', $params);

    $requests = $this->plugin->getReceivedRequests();
    $this->assertNotEmpty($requests);

    $request = array_pop($requests);
    $this->assertEquals('POST', $request->getMethod());
    $this->assertEquals('localhost', $request->getHost());
    $this->assertEquals('/geoserver/rest/path', $request->getPath());

    /*
    print_r( $request->getHeaders()->toArray());
    print_r( $request->getParams()->toArray());
    //$this->assertEquals($params, $request->getQuery()->urlEncode());
    */
  }

  public function testPut() {

  }

  public function testDelete() {

  }

  protected function tearDown() {

  }

  }
