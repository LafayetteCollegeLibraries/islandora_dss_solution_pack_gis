<?php

namespace GeoServer;

include_once __DIR__ . "/vendor/autoload.php";
include_once __DIR__ . "/Client.php";

use GeoServer\Client as Client;

/**
 * Class for the GeoServerClient: A GeoServer RESTful consumer
 * @author griffinj@lafayette.edu
 *
 */
class Session {

  public $url;
  public $user;
  public $pass;
  protected $client;

  /**
   * Static method for parsing the cookie file
   * Much of this was copied from the geoserver Module
   * This is for cases in which issues within Guzzle are raised
   *
   */
  public static function geoserver_parse_cookiefile($file) {

    // Parse cookie file.
    $cookies = array();
    $lines = @file($file);
    if($lines===FALSE) {

      throw new \Exception("Couldn't read cookies for GeoServer. Did your session expire?");
      return array();
    }
    foreach ($lines as $line) {

      if (substr($line, 0, 2) === '# ') {

        continue;
      }
      $columns = explode("\t", $line);
      if (isset($columns[5]) && isset($columns[6])) {

        $cookies[$columns[5]] = mb_substr($columns[6], 0, -1);
      }
    }
    return $cookies;
  }

  public function __construct($user, $pass, $url = 'http://localhost:8080/geoserver/rest', $client = NULL) {

    $this->url = rtrim($url, '/') . '/';
    $this->user = $user;
    $this->pass = $pass;

    $this->client = is_null($client) ? new Client($this) : $client;
  }

  /**
   * Alias for Client::workspace
   *
   */
  public function workspace($name) {

    return $this->client->workspace($name);
  }
}
