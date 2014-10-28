<?php namespace IslandoraGeoServer;

include_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class for the Islandora/GeoServer API
 * @author griffinj@lafayette.edu
 *
 */

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\CurlAuth\CurlAuthPlugin;

class GeoServerSession {

  public $url;
  public $user;
  public $pass;

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

  public function __construct($user, $pass, $url = 'http://localhost:8080/geoserver/rest') {

    $this->url = rtrim($url, '/') . '/';
    $this->user = $user;
    $this->pass = $pass;
  }
}

class IslandoraGeoServerSession {

  public $url;
  public $user;
  public $pass;

  public static function geoserver_parse_cookiefile($file) {

    // Parse cookie file.
    $cookies = array();
    $lines = @file($file);
    if($lines===FALSE) {

      /*
      watchdog('geoserver',
	       t("Couldn't read cookies for GeoServer. Did your session expire?"),
	       NULL,
	       WATCHDOG_ERROR
	       );
      */

      throw new \Exception("Couldn't read cookies for GeoServer. Did your session expire?");
      return array();
    }
    foreach ($lines as $line) {
      if (substr($line, 0, 2) === '# ') {
	continue;
      }
      $columns = explode("\t", $line);
      if (isset($columns[5]) && isset($columns[6])) {

	//$cookies[$columns[5]] = drupal_substr($columns[6], 0, -1);
	$cookies[$columns[5]] = mb_substr($columns[6], 0, -1);
      }
    }
    return $cookies;
  }

  public function __construct($user, $pass, $url = 'http://localhost:8080/geoserver/rest') {

    $this->url = rtrim($url, '/') . '/';
    $this->user = $user;
    $this->pass = $pass;
  }
}

class IslandoraGeoServerClient {

  private $client;

  public $workspace;
  public $datastore;
  public $name;

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

  private function authenticate($user, $pass) {

    $res = $this->post('j_spring_security_check', array('username' => $user,
							'password' => $pass));

    try {

      self::handle_http_codes($res->getStatusCode());
      $this->url .= 'rest';
    } catch(Exception $e) {

      throw $e;
    }
  }

  public function __construct($session, $client = NULL) {

    $this->session = $session;
    $this->url = $this->session->url;
    $user = $this->session->user;
    $pass = $this->session->pass;

    $this->client = $client;
    if(is_null($this->client)) {

      $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
      $authPlugin = new CurlAuthPlugin($user, $pass);

      $this->client = new Client($this->url);
      $this->client->addSubscriber($cookiePlugin);
      //$this->client->addSubscriber($authPlugin);
    }

    $this->authenticate($user, $pass);
  }

  public function workspace($name) {

    $this->workspace = new GeoServerWorkspace($this, $name);
    return $this->workspace;
  }

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
    print_r($params);

    $request = call_user_func_array(array($this->client, $method), $params);
    try {

      $response = $request->send();
    } catch(ClientErrorResponseException $e) {

      $response = $e->getResponse();
    }

    return $response;
  }

  public function delete($path, $params, $headers = array()) {

    //$url = $this->url . '/' . $path;

    // DELETE requests always require authentication
    //$this->authenticate($url, $this->session->user, $this->session->pass);
    return $this->request('delete', $path, $headers, $params);
  }

  public function put($path, $params, $headers = array()) {

    return $this->request('put', $path, $headers, $params);
  }

  public function post($path, $params, $headers = array()) {

    return $this->request('post', $path, $headers, $params);
  }

  /**
   * @todo Integrate caching functionality
   *
   */
  public function get($path, $params = array(), $headers = array()) {

    return $this->request('get', $path, $headers, $params);
  }
}


interface GeoServerRestful {

  /**
   * Create remote resource.
   */
  public function create();

  /**
   * Update remote resource.
   */
  public function update();

  /**
   * Delete remote resource.
   */
  public function delete();

  /**
   * Read remote resource.
   */
  public function read();
}

/**
 * @see geoserver_resource
 *
 */

abstract class GeoServerResource {

  public $name;

  function __construct($client, $name) {

    $this->client = $client;
    $this->name = $name;

    $this->read();
  }

  private function request($method, $path) {

    $request_method_map = array('create' => 'post',
				'update' => 'put',
				'delete' => 'delete',
				'read' => 'get');

    if(array_key_exists($method, $request_method_map)) {

      throw new Exception("Operation $method not supported.");
    }

    call_user_func(array($client, $request_method_map[$method]), $path);
  }
}


class GeoServerWorkspace extends GeoServerResource {

  /**
   * Create remote resource.
   */
  public function create() {

    $this->client->post('workspaces', array());
  }

  /**
   * Load all datastores and coveragestores
   *
   */
  public function read() {

    $response = $this->client->get('workspaces/' . $this->name . '.json', array(), array('content-type' => 'application/json'));
    $data = $response->json();

    if(array_key_exists('workspace', $data)) {

      foreach($data['workspace'] as $property => $value) {

	$values = array();
	switch($property) {

	case 'dataStores':

	  // Retrieve the data stores
	  $response = $this->client->get('workspaces/' . $this->name . '/datastores.json', array(), array('content-type' => 'application/json'));
	  $data = $response->json();

	  if(array_key_exists('dataStores', $data) and !empty($data['dataStores'])) {

	    foreach($data['dataStores'] as $key => $value) {

	      $data_store = array_shift($value);
	      $values[$data_store['name']] = new GeoServerDataStore($this->client, $data_store['name'], $this);
	    }
	  }

	  $this->{$property} = $values;
	  break;

	case 'coverageStores':

	  // Retrieve the coverage stores
	  $response = $this->client->get('workspaces/' . $this->name . '/coveragestores.json', array(), array('content-type' => 'application/json'));
	  $data = $response->json();

	  if(array_key_exists('coverageStores', $data) and !empty($data['coverageStores'])) {

	    foreach($data['coverageStores'] as $key => $value) {

	      $coverage_store = array_shift($value);
	      $values[$coverage_store['name']] = new GeoServerCoverageStore($this->client, $coverage_store['name'], $this);
	    }
	  }
	  $this->{$property} = $values;
	  break;

	default:

	  $this->{$property} = $value;
	  break;
	}
      }
    } else {

      throw new \Exception("Workspace could not be retrieved: " . $this->name);
    }

    // Load a data store
  }

  /**
   * Update remote resource.
   */
  function update() {

    $this->client->put('workspaces', array());
  }

  /**
   * Delete remote resource.
   */
  function delete() {

    $this->client->delete('workspaces', array());
  }

  /**
   * Create a coverage store
   *
   */
  public function createCoverageStore($name, $file_path = NULL) {

    $coverage_store = new GeoServerCoverageStore($this->client, $name, $this, $file_path);
    $this->coverageStores[$name] = $coverage_store;
    return $coverage_store;
  }

  /**
   * Delete a coverage store
   *
   */
  public function deleteCoverageStore($name) {

    if(!array_key_exists($name, $this->coverageStores)) {

      throw new Exception("Coverage store does not exist: $name");
    }

    $coverage_store = $this->coverageStores[$name];
    return $coverage_store->delete();
  }

  /**
   * Create a data store
   *
   */
  public function createDataStore($name, $file_path = NULL) {

    $data_store = new GeoServerDataStore($this->client, $name, $this, $file_path);
    $this->dataStores[$name] = $data_store;
    return $data_store;
  }

  /**
   * Delete a data store
   *
   */
  public function deleteDataStore($name) {

    if(!array_key_exists($name, $this->dataStores)) {

      throw new Exception("Data store does not exist: $name");
    }

    $data_store = $this->dataStores[$name];
    return $data_store->delete();
  }
}

/**
 * Class for handling vector data sets
 * @todo Implement
 */

class GeoServerDatastore extends GeoServerResource {

  const FILE = 0;
  const URL = 1;
  const EXTERNAL = 2;

  public static function extension_to_str($extension) {

    switch($extension) {
    case FILE:

      $value = 'file';
      break;
    case URL:

      $value = 'url';
      break;
    case EXTERNAL:

      $value = 'external';
      break;
    default:

      throw new Exception("Unsupported datastore extension: " . $extension);
    }

    return $value;
  }

  public $workspace;
  private $base_path;
  private $post_put_path;

  //function __construct($client, $name, $workspace, $resource_type = FILE, $resource_type_extension) {
  function __construct($client, $name, $workspace, $file_path = NULL) {

    $this->workspace = $workspace;
    $this->base_path = 'workspaces/' . $this->workspace->name . '/datastores';
    $this->file_path = $file_path;

    parent::__construct($client, $name);
  }

  /**
   * Create a data store
   * @todo Abstract for other entities within GeoServer
   *
   */
  public function create($file_path = NULL) {

    if(is_null($file_path)) {

      $file_path = $this->file_path;
    }
    $fh = fopen($file_path, "rb");

    if(!preg_match('/zip?$/', $file_path)) {

      throw new \Exception("Unsupported file format for $file_path");
    }
    $response = $this->client->put($this->base_path . '/' . $this->name . '/file.shp',
				   $fh,
				   array('content-type' => 'application/zip'));

    if(!$response->isSuccessful()) {

      throw new Exception("Failed to create a coverage store from $file_path");
    }

    return $this->read();
  }

  /**
   * Load all data stores
   *
   */
  protected function read() {

    //print $this->base_path . '/' . $this->name . '.json';
    $response = $this->client->get($this->base_path . '/' . $this->name . '.json', array(), array('content-type' => 'application/json'));
    //$response = $this->client->get($this->base_path . '/' . $this->name . '.json', array('content-type' => 'application/json'));

    // If this coverage store cannot be found, and if a file path was set...
    if($response->getStatusCode() == 404 and !is_null($this->file_path)) {

      // ...attempt to create the coverage store.
      return $this->create();
    } elseif(!$response->isSuccessful()) {

      throw new \Exception("Failed to retrieve the data store $name");
    }

    //print_r((string) $response);

    print $response->getStatusCode();

    $data = $response->json();
    print_r($data);

    /*
    foreach($data['dataStore'] as $property => $value) {

      $values = array();

      switch($property) {

      case 'coverages':

	// Retrieve the coverage stores
	$response = $this->client->get($this->base_path . '/' . $this->name . '/coverages.json', array(), array('content-type' => 'application/json'));
	$data = $response->json();

	if(array_key_exists('coverages', $data) and !empty($data['coverages'])) {

	  foreach($data['coverages'] as $key => $value) {

	    $coverage = array_shift($value);
	    $values[$coverage['name']] = new GeoServerCoverage($this->client, $coverage['name'], $this);
	  }
	  $this->{$property} = $values;
	}
	break;

      default:

	break;
      }
    }
    */

    return $this;
  }

  /**
   * Update remote resource.
   */
  function update($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    $this->client->put($this->put_path, array());
  }

  /**
   * Delete remote resource.
   */
  function delete($recurse = FALSE) {

    $this->client->delete($this->base_path, array('recurse' => $recurse));
  }
}


/**
 * Class for handling a vector data set
 * @todo Implement
 */
class GeoServerFeatureType extends GeoServerResource {

}

/**
 * Class for handling raster data sets
 *
 */
class GeoServerCoverageStore extends GeoServerResource {

  public $workspace;
  private $base_path;

  function __construct($client, $name, $workspace, $file_path = NULL) {

    $this->workspace = $workspace;
    $this->base_path = 'workspaces/' . $this->workspace->name . '/coveragestores';
    $this->file_path = $file_path;

    parent::__construct($client, $name);
  }

  /**
   * Create a coverage store
   * @todo Abstract for other entities within GeoServer
   *
   */
  public function create($file_path = NULL) {

    if(is_null($file_path)) {

      $file_path = $this->file_path;
    }
    $fh = fopen($file_path, "rb");

    if(!preg_match('/tiff?$/', $file_path)) {

      throw new \Exception("Unsupported file format for $file_path");
    }
    $response = $this->client->put($this->base_path . '/' . $this->name . '/file.geotiff',
				   $fh,
				   array('content-type' => 'image/tiff'));

    if(!$response->isSuccessful()) {

      throw new Exception("Failed to create a coverage store from $file_path");
    }

    return $this->read();
  }

  /**
   * Load all coveragestores
   *
   */
  protected function read() {

    $response = $this->client->get($this->base_path . '/' . $this->name . '.json', array(), array('content-type' => 'application/json'));

    // If this coverage store cannot be found, and if a file path was set...
    if($response->getStatusCode() == 404 and !is_null($this->file_path)) {

      // ...attempt to create the coverage store.
      return $this->create();
    } elseif(!$response->isSuccessful()) {

      throw new \Exception("Failed to retrieve the coverage store $name");
    }

    $data = $response->json();

    foreach($data['coverageStore'] as $property => $value) {

      $values = array();

      switch($property) {

      case 'coverages':

	// Retrieve the coverage stores
	$response = $this->client->get($this->base_path . '/' . $this->name . '/coverages.json', array(), array('content-type' => 'application/json'));
	$data = $response->json();

	if(array_key_exists('coverages', $data) and !empty($data['coverages'])) {

	  foreach($data['coverages'] as $key => $value) {

	    $coverage = array_shift($value);
	    $values[$coverage['name']] = new GeoServerCoverage($this->client, $coverage['name'], $this);
	  }
	  $this->{$property} = $values;
	}
	break;

      default:

	break;
      }
    }

    return $this;
  }

  /**
   * Update a coverage store
   * Update the name or the workspace for a given coverage store
   * @todo Implement
   *
   */
  function update($name = NULL, $workspace = NULL) {

    return FALSE;
  }

  /**
   * Delete remote resource.
   */
  function delete($recurse = 'true', $purge = 'none') {

    $params = array('recurse' => $recurse,
		    'purge' => $purge);

    $response = $this->client->delete($this->base_path . '/' . $this->name . '.json?' . http_build_query($params), // Work-around; @todo Investigate why passing these as options fails for Guzzle
				      NULL,
				      array('content-type' => 'application/json'));

    if(!$response->isSuccessful()) {

      throw new \Exception("Failed to delete the coverage store: " . $this->name . ' ' . $response->getStatusCode());
    }

    return TRUE;
  }

  /**
   * Create a coverage
   *
   */
  public function createCoverage($name, $file_path) {

    /*
    //$this->coverageStores[$name] = new GeoServerCoverageStore($this->client, $name, $this);
    if(!array_key_exists($name, $this->coverageStores)) {

      $this->create($file_path);
    }
    */
  }
}

/**
 * Class for handling a raster data set
 *
 */

class GeoServerCoverage extends GeoServerResource {

  public $coveragestore;
  private $base_path;

  function __construct($client, $name, $coverage_store) {

    $this->coverage_store = $coverage_store;
    $this->base_path = 'workspaces/' . $this->coverage_store->workspace->name . '/coveragestores/' . $this->coverage_store->name . '/coverages';

    $this->post_path = $this->base_path;

    parent::__construct($client, $name);

    /*
    $this->get_path = $this->base_path . '/' . $this->name;
    $this->put_path = $this->get_path;
    $this->delete_path = $this->put_path;
    */
  }

  /**
   * Create remote resource.
   */
  public function create() {

    return $this->client->post($this->post_path);
  }

  /**
   * Read remote resource.
   */
  public function read() {

    //return $this->client->get($this->get_path);
    $response = $this->client->get($this->base_path . '/' . $this->name . '.json', array(), array('content-type' => 'application/json'));

    $data = $response->json();
  }

  /**
   * Update remote resource.
   */
  function update($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    $this->client->put($this->put_path, array());
  }

  /**
   * Delete remote resource.
   */
  function delete($recurse = FALSE) {

    $this->client->delete($this->delete_path, array('recurse' => $recurse));
  }
}

/**
 * Class for managing raster layers within GeoServer derived from Islandora Large Image Objects
 * @see geoserver_layer_type_coverage
 * @see geoserver_layer_type
 *
 */
class IslandoraGeoServerCoverage implements \Serializable {

  public function __construct($object, $session, $client = NULL, $workspace = NULL, $coverage_store = NULL) {

    $this->object = $object;

    // Always use the deepest parent Collection for the Coverage Store name
    $collection_name = array_pop($this->object->getParents());

    // If a client hasn't been passed, generate a new client using the existing session
    $this->client = $client;
    if(is_null($this->client)) {

      $this->client = new IslandoraGeoServerClient($session);
    }

    // If the workspace and hasn't been passed, either use the existing session workspace or the "default" workspace
    $this->workspace = $workspace;
    if(is_null($this->workspace)) {

      $this->workspace = $session->workspace ?: new GeoServerWorkspace('default', $this->client);
    }

    $this->coverage_store = $coverage_store;
    if(is_null($this->coverage_store)) {

      $this->workspace = $session->workspace->coverage_store ?: new GeoServerCoverageStore($collection_name, $this->client);
    }

    // Finally, retrieve the new Coverage
    $this->coverage = new GeoServerCoverage($this->object->id, $this->client);
  }

  function serialize() {

    // Get the URL for the Coverage
    $url = $this->coverage->url;

    // Update the content in the repository
    $this->object['OBJ']->parseFromUrl($url);

    return $url;
  }

  function unserialize($url = NULL) {

    if(is_null($url)) {

      $url = $this->object['OBJ']->url;
    }

    // Synchronize the state of the Object's raster data with that of the Coverage content
    $this->coverage->update($url);
  }
}

/*
class IslandoraGeoServerLayer extends GeoServerResource {

  /**
   * Read remote feature type.
   * @see geoserver_resource_feature_type::read().
   * /
  public function read() {

    $url = "rest/workspaces/{$this->workspace}/datastores/{$this->datastore}/featuretypes/{$this->name}.json";
    try {
      $result = geoserver_get($url);
      $this->feature_type = $result->data;
    } catch (geoserver_resource_http_exception $exc) {
      throw new geoserver_resource_exception(
        t("Could not read feature types from data store @datastore of workspace @workspace from GeoServer: @exception", array(
          "@datastore" => $this->datastore,
          "@workspace" => $this->workspace,
          "@exception" => $exc->getMessage()
        )));
    }

    try {
      $result = geoserver_get("rest/layers/{$this->workspace}:{$this->name}.json");
      $this->layer = $result->data;
    } catch (geoserver_resource_http_exception $exc) {
      throw new geoserver_resource_exception(t("Could not read layer @layer from GeoServer: @exception", array(
        "@layer" => $this->name,
        "@exception" => $exc->getMessage()
      )));
    }
  }
}
*/
