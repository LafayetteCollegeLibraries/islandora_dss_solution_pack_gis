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
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200, array('Content-Type' => 'application/json'), json_encode(array('workspace' => array()))));
    $workspace_a = new Workspace($client, 'test_workspace_a');

    // Mock for workspace B
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200, array('Content-Type' => 'application/json'), json_encode(array('workspace' => array()))));
    $workspace_b = new Workspace($client, 'test_workspace_b');

    // Test the mutator and accessor
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200, array('Content-Type' => 'application/json'), json_encode(array('workspace' => array()))));

    // Accessing multiple workspaces using a single client instance
    $this->assertEquals($workspace_a, $client->workspace('test_workspace_a'));
$this->plugin->addResponse(new Guzzle\Http\Message\Response(200, array('Content-Type' => 'application/json'), json_encode(array('workspace' => array()))));

    $this->assertEquals($workspace_b, $client->workspace('test_workspace_b'));
  }

  public function testGet() {

    $client = new Client($this->session, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $client->get('workspaces/default');

    $response = array_pop($this->plugin->getReceivedRequests());
    $this->assertEquals($this->url . '/rest' . '/workspaces/default', $response->getUrl());
  }

  public function testPost() {

    $client = new Client($this->session, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $data = array('name' => 'test_workspace_c');

    $client->post('workspaces', $data);

    $response = array_pop($this->plugin->getReceivedRequests());
    $this->assertEquals($this->url . '/rest' . '/workspaces', $response->getUrl());
  }

  public function testPut() {

    $client = new Client($this->session, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $data = array('name' => 'test_workspace_d');

    $client->put('workspaces/test_workspace_c', $data);

    $response = array_pop($this->plugin->getReceivedRequests());
    $this->assertEquals($this->url . '/rest' . '/workspaces/test_workspace_c', $response->getUrl());
  }

  public function testDelete() {

    $client = new Client($this->session, $this->consumer);

    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $client->delete('workspaces/test_workspace_c');

    $response = array_pop($this->plugin->getReceivedRequests());
    $this->assertEquals($this->url . '/rest' . '/workspaces/test_workspace_c', $response->getUrl());
  }
}