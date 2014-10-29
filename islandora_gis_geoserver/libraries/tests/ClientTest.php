<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../Client.php";
require_once __DIR__ . "/../Workspace.php";

use GeoServer\Client as Client;
use GeoServer\Session as Session;
use GeoServer\Workspace as Workspace;

class ClientTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->plugin = new Guzzle\Plugin\Mock\MockPlugin();
    $this->consumer = new Guzzle\Http\Client();
    $this->consumer->addSubscriber($this->plugin);

    $this->url = 'https://localhost/geoserver';
    $this->pass = 'secret';

    $this->session = new Session('admin', $this->pass, $this->url, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
  }

  public function testAuthenticate() {

    // Test for a successful authentication with GeoServer

    try {

      $client = new Client($this->session, $this->consumer);
    } catch(Exception $e) {

      $this->fail($e->getMessage());
    }
  }

  public function testWorkspace() {

    $client = new Client($this->session, $this->consumer);

    // Mock for workspace A
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $workspace_a = new Workspace($client, 'test_workspace_a');

    // Mock for workspace B
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $workspace_a = new Workspace($client, 'test_workspace_b');

    // Test the mutator and accessor
    $client->workspace('test_workspace_a');
    $this->assertEquals($workspace_a, $client->workspace);

    // Test the alias
    $client->workspace = 'test_workspace_b';
    $this->assertEquals($workspace_b, $client->workspace);
  }

  public function testGet() {

    $client = new Client($this->session, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $client->get('default');

    print_r(array_pop($this->plugin->getReceivedRequests()));
    //$this->assertContainsOnly($request, $plugin->getReceivedRequests());
  }

  public function testPost() {

  }

  public function testPut() {

  }

  public function testDelete() {

  }
}