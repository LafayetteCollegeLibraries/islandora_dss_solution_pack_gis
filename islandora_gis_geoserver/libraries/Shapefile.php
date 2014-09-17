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

  public $geoserver_session;
  public $shapefiles;
  public $coverage_name;

  // hasDerivation
  // GeoServerCoverageStore
  function __construct($islandora_session,
		       $geoserver_session = NULL,
		       $pid = NULL,
		       $workspace = 'default',
		       $object = NULL) {

    parent::__construct($islandora_session, $pid, $object);

    //$this->coverage_name = preg_replace('/\:/', '_', $this->object->id);
    $this->coverage_name = preg_replace('/\:/', '_', $pid);

    if(!is_a($this->object, 'FedoraObject')) {

      throw new Exception("Failed to pass a tuque FedoraObject to the IslandoraGeoImage constructor");
    }

    if(!is_null($geoserver_session)) {

      $this->client = new IslandoraGeoServerClient($geoserver_session);
      $workspace = $this->client->workspace($workspace);

      //$file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
      $file_path = '/tmp/IslandoraGeoImage_' . $this->coverage_name . '.tiff';

      $ds_obj = $this->datastream('OBJ');
      $ds_obj->getContent($file_path);

      $workspace->createCoverageStore($this->coverage_name, $file_path);
      unlink($file_path);
    }
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

  function __construct($session,
		       $geoserver_session = NULL,
		       $pid = NULL, $object = NULL) {

    parent::__construct($session, $pid, $object);

    $this->geoserver_session = $geoserver_session;

    $this->base_maps = array();
    $this->get_base_maps();

    if(!is_null($geoserver_session)) {

      $this->client = new IslandoraGeoServerClient($geoserver_session);
      $workspace = $this->client->workspace($workspace);

      //$file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
      $file_path = '/tmp/IslandoraGeoImage_' . $this->coverage_name . '.shp';

      $ds_obj = $this->datastream('OBJ');
      $ds_obj->getContent($file_path);

      $workspace->createDataStore($this->coverage_name, $file_path);
      unlink($file_path);
    }
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
