<?php

/**
 * Unit tests for the Session tests
 *
 */

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../Session.php";

use GeoServer\Session as Session;

class SessionTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->plugin = new Guzzle\Plugin\Mock\MockPlugin();
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $this->consumer = new Guzzle\Http\Client();
    $this->consumer->addSubscriber($this->plugin);

    $this->url = 'https://localhost/geoserver';
    $this->pass = 'secret';
  }

  public function testConstruct() {

    try {

      $this->session = new Session('admin', $this->pass, $this->url, $this->consumer);
    } catch(Exception $e) {

      $this->fail($e->getMessage());
    }
  }
}
