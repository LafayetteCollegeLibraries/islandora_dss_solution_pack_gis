<?php

include_once __DIR__ . "/vendor/autoload.php";
include_once __DIR__ . "/Resource.php";

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

    // Work-around; @todo Investigate why passing these as options fails for Guzzle
    $response = $this->client->delete($this->base_path . '/' . $this->name . '.json?' . http_build_query($params),
				      NULL,
				      array('content-type' => 'application/json'));

    if(!$response->isSuccessful()) {

      throw new \Exception("Failed to delete the coverage store: " . $this->name . ' ' . $response->getStatusCode());
    }

    return TRUE;
  }

  /**
   * Create a coverage
   * @todo Implement
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