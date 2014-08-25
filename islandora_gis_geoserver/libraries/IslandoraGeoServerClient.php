<?php namespace IslandoraGeoServer;

require 'vendor/autoload.php';

  /**
   * Class for the Islandora/GeoServer API
   * @author griffinj@lafayette.edu
   *
   */

/*
 $client = new GuzzleHttp\Client();
$response = $client->get('http://guzzlephp.org');
$res = $client->get('https://api.github.com/user', ['auth' =>  ['user', 'pass']]);
echo $res->getStatusCode();
// 200
echo $res->getHeader('content-type');
// 'application/json; charset=utf8'
echo $res->getBody();
// {"type":"User"...'
var_export($res->json());
// Outputs the JSON decoded data
*/

class IslandoraGeoServerSession {

  public $url;
  public $user;
  public $pass;

  public function __construct($url = 'http://localhost:8080/geoserver/rest', $user = 'admin', $pass) {

    $this->url = $url;
    $this->user = $user;
    $this->pass = $pass;
  }
}

class IslandoraGeoServerClient {

  private $client;
  public $workspace;
  public $datastore;
  public $name;

  private function authenticate($user = 'admin', $pass) {

    $this->client->get($this->url, array('auth' => array($user, $pass)));
    if($res->getStatusCode() != 200) {

      throw new Exception("Could not authenticate as $user for $url.");
    }
  }

  public function __construct($session) {

    $this->client = new GuzzleHttp\Client();

    $this->url = $this->session->url;
    $user = $this->session->user;
    $pass = $this->session->pass;

    $this->authenticate($user, $pass);
  }

  private function request($method, $path, $params = array()) {
    
    $url = $this->url . "/$path";

    try {

      if(!object_has_method($this->client, $method)) {

	throw new Exception($this->client . " does not support the HTTP method $method");
      }

      $response = call_user_func(array($this->client, $method), $url, array('json' => $params));
    } catch (RequestException $e) {

      echo $e->getRequest() . "\n";

      if($e->hasResponse()) {

        echo $e->getResponse() . "\n";
      }
    }

    return $response;
  }

  public function delete($path, $params) {

    return $this->request('delete', $path);
  }

  public function put($path, $params) {

    return $this->request('put', $path);
  }

  public function post($path, $params) {

    return $this->request('post', $path);
  }

  /**
   * @todo Integrate caching functionality
   *
   */
  public function get($path, $params = array()) {

    return $this->request('get', $path);

    /*
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
    */
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

    $this->client->post('/workspaces', array());
  }

  /**
   * Update remote resource.
   */
  function update() {

    $this->client->put('/workspaces', array());
  }

  /**
   * Delete remote resource.
   */
  function delete() {

    $this->client->delete('/workspaces', array());
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

  function __construct($client, $name, $workspace, $resource_type = FILE, $resource_type_extension) {

    $this->workspace = $workspace;
    $this->base_path = '/workspaces/' . $this->workspace->name . '/datastores';

    $this->resource_type = $resource_type;

    $this->post_path = $this->base_path . '/' . self::extension_to_str($this->resource_type);
    $this->put_path = $this->post_path;

    self::parent($client, $name);
  }

  /**
   * Create remote resource.
   */
  public function create($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    $this->client->post($this->post_path, array());
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

  function __construct($client, $name, $workspace) {

    $this->workspace = $workspace;
    $this->base_path = '/workspaces/' . $this->workspace->name . '/coveragestores';

    self::parent($client, $name);
  }

  /**
   * Create remote resource.
   */
  public function create($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    $this->client->post($this->post_path, array());
  }

  /**
   * Update remote resource.
   */
  function update($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    // The Content Type must be explicitly set

    $this->client->put($this->put_path, array());
  }

  /**
   * Delete remote resource.
   */
  function delete($recurse = FALSE, $purge = 'none') {

    // The Content Type must be explicitly set

    $this->client->delete($this->base_path, array('recurse' => $recurse));
  }
}

/**
 * Class for handling a raster data set
 *
 */

class GeoServerCoverage extends GeoServerResource {

  public $workspace;
  private $base_path;

  function __construct($client, $name, $workspace) {

    $this->workspace = $workspace;
    $this->base_path = '/workspaces/' . $this->workspace->name . '/coveragestores';

    self::parent($client, $name);
  }

  /**
   * Create remote resource.
   */
  public function create($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    $this->client->post($this->post_path, array());
  }

  /**
   * Update remote resource.
   */
  function update($file, $extension = 'shp', $configure = 'first', $target = 'shp', $update = 'append', $charset = 'utf-8') {

    // The Content Type must be explicitly set

    $this->client->put($this->put_path, array());
  }

  /**
   * Delete remote resource.
   */
  function delete($recurse = FALSE, $purge = 'none') {

    // The Content Type must be explicitly set

    $this->client->delete($this->base_path, array('recurse' => $recurse));
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
