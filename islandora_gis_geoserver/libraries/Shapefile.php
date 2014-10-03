<?php

  /**
   * @file Object-Oriented API for Islandora Shapefiles
   * @author griffinj@lafayette.edu
   *
   */

include_once __DIR__ . "/IslandoraGeoServerClient.php";
use IslandoraGeoServer\IslandoraGeoServerSession;
use IslandoraGeoServer\IslandoraGeoServerClient;

/**
 * Class for Islandora GeoTIFF Images
 *
 */

define('FEDORA_RELS_EXT_URI', 'info:fedora/fedora-system:def/relations-external#');

class IslandoraGeoImage extends IslandoraLargeImage {

  //public $geoserver_client;
  public $shapefiles;
  public $coverage_name;

  // hasDerivation
  // GeoServerCoverageStore
  function __construct($islandora_session,
		       $geoserver_session = NULL,
		       $pid = NULL,
		       $workspace_name = 'default',
		       $object = NULL) {

    parent::__construct($islandora_session, $pid, $object);

    //$this->coverage_name = preg_replace('/\:/', '_', $this->object->id);
    $this->coverage_name = preg_replace('/\:/', '_', $pid);

    if(!is_a($this->object, 'FedoraObject')) {

      throw new Exception("Failed to pass a tuque FedoraObject to the IslandoraGeoImage constructor");
    }

    // If a GeoServer Session Object was passed to the constructor, retrieve the workspace
    if(!is_null($geoserver_session)) {

      //! @todo All Client instances should be retrieved from a single Session Object
      //$this->geoserver_client = new IslandoraGeoServerClient($geoserver_session);
      //$geoserver_client = new IslandoraGeoServerClient($geoserver_session);
      $geoserver_client = $geoserver_session->client();

      //! @todo Equally, all workspaces should bear a one-to-one relationship with each Session Object
      $this->workspace = $geoserver_client->workspace($workspace_name);

      $this->push();
    }
  }

  /**
   * Update the state of the GeoServer Coverage using the Islandora Large Image Object
   *
   */
  private function push() {

    //$file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
    $file_path = '/tmp/IslandoraGeoImage_' . $this->coverage_name . '.tiff';

    $ds_obj = $this->datastream('OBJ');
    $ds_obj->getContent($file_path);

    $this->workspace->createCoverageStore($this->coverage_name, $file_path);
    unlink($file_path);
  }

  /**
   * Update the state of the GeoServer Coverage using the Islandora Large Image Object
   *
   */
  function update() {

    $file_path = '/tmp/IslandoraGeoImage_' . $this->coverage_name . '.tiff';

    $ds_obj = $this->datastream('OBJ');
    $ds_obj->getContent($file_path);

    $this->workspace->updateCoverageStore($this->coverage_name, $file_path);
    unlink($file_path);
  }

  function add_shapefile($shapefile) {

    $shapefile_uri = $shapefile->id;

    $this->object->relationships->add(FEDORA_RELS_EXT_URI, 'hasDependent', $shapefile_uri);
  }

  function to_layer() {

    $layer = new stdClass();
    $layer->name = $this->coverage_name;
    $layer->title = $this->object->label;

    $layer->data = array('openlayers' => array('gwc_data' => array('isBaseLayer' => TRUE,
								   'projection' => array('EPSG:900913', 'EPSG:3857'))));

    return $layer;
    /*
    $openlayers = $gs_layer->data['openlayers'];

    if ($openlayers['gwc']) {
      $data = isset($openlayers['gwc_data']) ? $openlayers['gwc_data'] : array();
     */

    /*
        'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
        'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:900913', 'EPSG:3857'),
        'params' => array(
          'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
     */
  }
}

/*
 * Class for Islandora Shapefiles
 *
 */

class IslandoraShapefile extends IslandoraObject {

  public $geoserver_session;
  public $base_maps;

  private $feature;

  function __construct($session,
		       $geoserver_session = NULL,
		       $pid = NULL, $object = NULL,
		       //$workspace = NULL,
		       $workspace = 'default',
		       $feature = NULL) {

    parent::__construct($session, $pid, $object);
    $this->feature_type_name = preg_replace('/\:/', '_', $pid);

    $this->geoserver_session = $geoserver_session;

    $this->base_maps = array();
    $this->get_base_maps();

    if(!is_null($geoserver_session)) {

      //! @todo All Client instances should be retrieved from a single Session Object
      //$this->geoserver_client = new IslandoraGeoServerClient($geoserver_session);
      //$geoserver_client = new IslandoraGeoServerClient($geoserver_session);
      $geoserver_client = $geoserver_session->client();

      //! @todo Equally, all workspaces should bear a one-to-one relationship with each Session Object
      $this->workspace = $geoserver_client->workspace($workspace_name);

      $this->push();

      /*
      $this->client = new IslandoraGeoServerClient($geoserver_session);

      if(is_null($workspace)) {

	$workspace = 'default';
      }

      $workspace = $this->client->workspace($workspace);

      // If a GeoServer Feature has been created, retrieve the compressed shapefile...
      if(!is_null($feature)) {

	$file_path = $feature->file_path;
      } else { // ...and, otherwise, retrieve the compressed Shapefile from the Islandora Object.

	$file_path = '/tmp/IslandoraGeoImage_' . $this->feature_type_name . '.zip';
	$this->load();

	// @todo Resolve
	//$ds_obj = $this->datastream('SHP');
	//$ds_obj->getContent($file_path);

	//$ds_obj = $this->object['OBJ'];
	$ds_obj = $this->object['SHP'];

	$ds_obj->getContent($file_path);
      }

      $workspace->dataStore($this->feature_type_name, $file_path);
      unlink($file_path);
      */
    }
  }

  private function push() {

    // If a GeoServer Feature has been created, retrieve the compressed shapefile...
    if(!is_null($this->feature)) {

      $file_path = $this->feature->file_path;
    } else { // ...and, otherwise, retrieve the compressed Shapefile from the Islandora Object.

      $file_path = '/tmp/IslandoraGeoImage_' . $this->feature_type_name . '.zip';
      //$this->load();

      //! @todo Resolve
      //$ds_obj = $this->datastream('SHP');
      $ds_obj = $this->object['SHP'];

      $ds_obj->getContent($file_path);
    }
    
    $workspace->dataStore($this->feature_type_name, $file_path);
    unlink($file_path);
  }

  function get_base_maps() {

    $query = 'SELECT $object $title $content
     FROM <#ri>
     WHERE {
              $object <fedora-rels-ext:hasDependent> <info:fedora/' . $this->object->id . '> ;
              <fedora-model:label> $title ;
              <fedora-model:hasModel> $content ;
              <fedora-model:state> <fedora-model:Active> .';
    $query .= '} ORDER BY $title';

    $query_array = array('query' => $query,
			 'type' => 'sparql',
			 //'pid' => $obj_pid,
			 // Seems as though this is ignored completely.
			 //'page_size' => $page_size,
			 //'page_number' => $page_number,
			 );

    foreach($this->session->connection->repository->ri->query($query_array['query'], $query_array['type']) as $result) {

      $this->base_maps[] = new IslandoraGeoImage($this->session, $this->geoserver_session, $result['object']['value']);
    }
  }

  function to_layer() {

  }
}
