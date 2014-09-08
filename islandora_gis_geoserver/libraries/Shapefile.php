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

  // hasDerivation
  // GeoServerCoverageStore
  function __construct($islandora_session,
		       $geoserver_session,
		       $pid = NULL, $object = NULL) {

    parent::__construct($islandora_session, $pid, $object);

    $coveragestore_name = preg_replace('/\:/', '_', $this->object->id);

    $this->client = new IslandoraGeoServerClient($geoserver_session);
    $workspace = $this->client->workspace('default');

    //$file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
    $file_path = '/tmp/IslandoraGeoImage_' . $coveragestore_name . '.tiff';

    $ds_obj = $this->object['OBJ'];
    $ds_obj->getContent($file_path);

    $workspace->createCoverageStore($coveragestore_name, $file_path);
    unlink($file_path);
  }

  function add_shapefile($shapefile) {

    $shapefile_uri = 'info:fedora/' . $shapefile->id;

    $this->object->relationships->add(FEDORA_RELS_EXT_URI, 'hasDependent', $shapefile_uri);
  }
}

/*
 * Class for Islandora Shapefiles
 *
 */

class IslandoraShapefile extends IslandoraObject {

  public $base_maps;

  function __construct($session,
		       $geoserver_session,
		       $pid = NULL, $object = NULL) {

    parent::__construct($session, $pid, $object);

    $this->geoserver_session;

    $this->base_maps = array();
    $this->get_base_maps();
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
    $query_array = array(
			 'query' => $query,
			 'type' => 'sparql',
			 //'pid' => $obj_pid,
			 // Seems as though this is ignored completely.
			 'page_size' => $page_size,
			 'page_number' => $page_number,
			 );

    foreach($this->session->connection->repository->ri->query($query_array['query'], $query_array['type']) as $result) {

      $this->base_maps[] = new IslandoraGeoImage($this->session, $this->geoserver_session, $result['object']['value']);
    }
  }

  }
