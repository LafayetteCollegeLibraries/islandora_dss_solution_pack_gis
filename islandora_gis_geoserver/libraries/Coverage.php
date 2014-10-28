<?php

include_once __DIR__ . "/vendor/autoload.php";
include_once __DIR__ . "/Resource.php";

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
