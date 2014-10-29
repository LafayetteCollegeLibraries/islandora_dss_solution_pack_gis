<?php

namespace GeoServer;

include_once __DIR__ . "/vendor/autoload.php";
include_once __DIR__ . "/Session.php";
include_once __DIR__ . "/Workspace.php";

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

use GeoServer\Workspace as Workspace;

class Client {

  private $client;

  public $workspace;
  public $datastore;
  public $name;

  /**
   * Static method for handling HTTP response codes
   *
   */
  private static function handle_http_codes($http_code) {

    switch($http_code) {

    case 0:
    case NULL:
    case FALSE:
      throw new \Exception("Failure to resolve the host");
      break;

    case 401:
      throw new \Exception("Authentication failed");
      break;

    case 405:

      throw new \Exception("No such method exists for this resource");
      break;

    default:

      break;
    }
  }

  /**
   * Method for authenticating against the GeoServer instance
   * @param string $user
   * @param string $pass
   *
   */
  private function authenticate($user, $pass) {

    /**
     * This is specific to GeoServer
     * The approach here replicates what was implemented using cURL within the geoserver Module
     *
     */
    $res = $this->post('j_spring_security_check', array('username' => $user,
							'password' => $pass));

    try {

      self::handle_http_codes($res->getStatusCode());
      $this->url .= 'rest';
    } catch(Exception $e) {

      throw $e;
    }
  }

  /**
   * Constructor for the client
   * @param GeoServerSession $session
   *
   */
  public function __construct($session, $client = NULL) {

    $this->session = $session;
    $this->url = $this->session->url;
    $user = $this->session->user;
    $pass = $this->session->pass;

    $this->client = $client;
    if(is_null($this->client)) {

      $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
      $authPlugin = new CurlAuthPlugin($user, $pass);

      $this->client = new GuzzleClient($this->url);
      $this->client->addSubscriber($cookiePlugin);
      //$this->client->addSubscriber($authPlugin);
    }

    $this->authenticate($user, $pass);
  }

  /**
   * Method for retrieving a workspace within a session
   *
   */
  public function workspace($name) {

    $this->workspace = new Workspace($this, $name);
    return $this->workspace;
  }

  /*
   * Retrieve the workspace by setting the property
   *
   */
  public function __set($name, $value) {

    switch($name) {

    case 'workspace':
      $this->workspace($value);
      break;

    default:
      $this->{$name} = $value;
      break;
    }
  }

  /**
   * Transmit a request over the HTTP
   * (Wrapper for Guzzle methods)
   *
   */
  private function request($method, $path) {

    $url = $this->url . "/$path";

    if(!method_exists($this->client, $method)) {

      throw new \Exception(get_class($this->client) . " does not support the HTTP method $method");
    }

    //$request = call_user_func(array($this->client, $method), $url, array('json' => $params));

    //$params = array_merge(array($url), array_splice(func_get_args(), 2));
    $params = array_splice(func_get_args(), 2);
    array_unshift($params, $url);

    $request = call_user_func_array(array($this->client, $method), $params);
    try {

      $response = $request->send();
    } catch(ClientErrorResponseException $e) {

      $response = $e->getResponse();
    }

    return $response;
  }

  /**
   * DELETE method over the HTTP
   *
   */
  public function delete($path, $params = array(), $headers = array()) {

    return $this->request('delete', $path, $headers, $params);
  }

  /**
   * PUT method over the HTTP
   *
   */
  public function put($path, $params, $headers = array()) {

    return $this->request('put', $path, $headers, $params);
  }

  /**
   * POST method over the HTTP
   *
   */
  public function post($path, $params, $headers = array()) {

    return $this->request('post', $path, $headers, $params);
  }

  /**
   * GET method over the HTTP
   * @todo Integrate caching for responses to GET requests
   *
   */
  public function get($path, $params = array(), $headers = array()) {

    return $this->request('get', $path, $headers, $params);
  }
}
