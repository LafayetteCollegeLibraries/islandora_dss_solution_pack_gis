<?php

namespace GeoServer;

include_once __DIR__ . "/vendor/autoload.php";
include_once __DIR__ . "/Resource.php";

use GeoServer\Resource as Resource;

class Workspace extends Resource {

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
