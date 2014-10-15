<?php

  /**
   * @file Functionality for the integration with the GeoServer WMS
   * @author griffinj@lafayette.edu
   *
   */

$module_path = drupal_get_path('module', 'islandora_dss');
include_once "$module_path/libraries/Islandora.php";

$module_path = drupal_get_path('module', 'islandora_gis_geoserver');
include_once "$module_path/libraries/IslandoraGeoServerClient.php";
include_once "$module_path/libraries/Shapefile.php";

/**
 * Get layer object
 *
 * @ingroup openlayers_api
 * @param $reset
 *   Boolean whether to reset cache or not
 * @return array
 *   array of layer info
 */
function islandora_gis_geoserver_get_layer_object($map) {

  /*
  // Static cache because this function will possibly be called in big loops.
  static $layer_types;
  if (!isset($layer_types)) {
    $layer_types = geoserver_layer_types();
  }

  $layer->title = t($layer->title);
  $layer->description = t($layer->description);

  if (is_array($layer->data) && isset($layer->data['type']) &&  isset($layer_types[$layer->data['type']])) {
    $layer_class = ctools_plugin_get_class($layer_types[$layer->data['type']], 'layer_type');
    if ($layer_class) {
      return new $layer_class($layer);
    }
  }
  watchdog('geoserver', 'Layer %name is unavailable because its layer type or the module that provides its layer type is missing',
    array('%name' => $layer->name), WATCHDOG_ERROR);
  return FALSE;
  */

  $layer->title = t($layer->title);
  $layer->description = t($layer->description);
}

/**
 * Implements hook_user_login().
 */
function islandora_gis_geoserver_user_login(&$edit, $account) {

  if (isset($edit['values'])) {

    /*
    $geoserver_login = geoserver_login($edit['values']['name'], $edit['values']['pass']);

    if ($geoserver_login !== TRUE) {
      drupal_set_message(t("Failed to authenticate you with GeoServer. Some map services won't be available."), 'error');
      watchdog('geoserver', 'Could not authenticate %username at GeoServer: !reason',
          array('%username' => urlencode($edit['values']['name']), '!reason' => $geoserver_login),
          WATCHDOG_ERROR);
    }
    */

    $session = new IslandoraGeoServer\IslandoraGeoServerSession($edit['values']['name'], $edit['values']['pass'], geoserver_url());
  }
}

function islandora_gis_geoserver_get_base_maps($object) {

  $connection = islandora_get_tuque_connection();

  $query = 'SELECT $object $title $content
     FROM <#ri>
     WHERE {
              $object <fedora-rels-ext:hasDependent> <info:fedora/' . $object->id . '> ;
              <fedora-model:label> $title ;
              <fedora-model:hasModel> $content ;
              <fedora-model:state> <fedora-model:Active> .';
  $query .= '} ORDER BY $title';

  $query_array = array('query' => $query,
		       'type' => 'sparql',
		       );

  //$layers = array();

  //foreach($connection->repository->ri->query($query_array['query'], $query_array['type']) as $result) {  
  //return $connection->repository->ri->query($query_array['query'], $query_array['type']);

  $results = array_map(function($result) {

      return $result['object']['value'];
    }, $connection->repository->ri->query($query_array['query'], $query_array['type']));

  // Ensure that the Islandora Objects are unique
  return array_map('islandora_object_load', array_unique($results));
}

/**
 * Retrieve the maximum extent bounding box for Islandora Large Image Objects related to Islandora Shapefile Objects
 *
 */
function islandora_gis_geoserver_max_extent($shp_object, $objects = NULL) {

  if(is_null($objects)) {

    $objects = islandora_gis_geoserver_get_base_maps($shp_object);
  }

  //$max_extent = array(-1, -1, 1, 1);
  $max_extent = NULL;

  if(!empty($objects)) {

    $object = $objects[0];

    $dc_doc = new SimpleXmlElement($object['DC']->content);
    $dc_doc->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

    $elems = $dc_doc->xpath('//dc:coverage[starts-with(text(), "xmin=")]');
    $dc_coverage = array_pop($elems);
    $values = explode(';', (string) $dc_coverage);

    $max_extent = array_map(function($value) {

	preg_match('/[a-z]{4}\=(.+)/', $value, $m);
	return $m[1];
      }, $values);
  }

  return $max_extent;
}

/**
 * Structure the Islandora Large Image Objects as WMS layers managed by the GeoServer
 *
 */
function islandora_gis_geoserver_base_maps_load($object) {

  $layers = array();

  foreach(islandora_gis_geoserver_get_base_maps($object) as $base_map_object) {

    //! @author griffinj@lafayette.edu
    $max_extent = islandora_gis_geoserver_max_extent($object);

    //dpm($max_extent);
    $default_zoom_extent = variable_set('islandora_gis_openlayers_default_zoom_extent', array());

    $max_extent = array(-8375376.6495653,
			4966152.1011441,
			-8369787.1918727,
			4968063.0268509);

    dpm($max_extent);

    $coverage_name = preg_replace('/\:/', '_', $base_map_object->id);

    $layer = new stdClass();
    $layer->name = $coverage_name;
    $layer->title = $base_map_object->label;

    $layer->data = array('openlayers' => array('wms' => TRUE,
					       'wms_data' => array('isBaseLayer' => FALSE,
								   'projection' => array('EPSG:900913', 'EPSG:3857'),
								   //'projection' => array('EPSG:4326'),
								   'maxResolution' => 'auto',
								   //'minResolution' => 'auto',
								   //'minExtent' => array(-1,-1,1,1),
								   'maxExtent' => $max_extent
								   )));
    $layers[] = $layer;
  }

  // Ensure that the Features for the Shapefile Object itself are also retrieved
  $coverage_name = preg_replace('/\:/', '_', $object->id);

  $layer = new stdClass();
  $layer->name = $coverage_name;
  $layer->title = $object->label;

  $layer->data = array('openlayers' => array('wfs' => TRUE,
					     'wfs_data' => array(
								 //'isBaseLayer' => TRUE,
								 'isBaseLayer' => FALSE,
								 //'projection' => array('EPSG:900913', 'EPSG:3857'),
								 'projection' => array('EPSG:4326'),
								 //'maxExtent' => $max_extent
								 )));

  $layers[] = $layer;

  return $layers;
}

/**
 * Structure the set of layers retrieved in relation to a given Islandora Shapefile Object
 * @see geoserver_layers_load()
 *
 */
function islandora_gis_geoserver_layers_load() {

  $layers = array();
  //$object_pid = variable_get('islandora_gis_view_object_pid', '');
  $object_pids = variable_get('islandora_gis_view_object_pids', '');

  //if(!empty($object_pid)) {
  foreach($object_pids as $object_pid) {

    dpm('trace5');
    dpm($object_pid);

    $session = islandora_dss_get_session();
    $geoserver_session = islandora_gis_geoserver_get_session();
    $object = islandora_object_load($object_pid);

    // Log the warning
    // @todo Implement

    // Attempt to retrieve the layers manually
    //! @todo Rename (one is no longer retrieving base maps, but, WMS and WFS layers)
    foreach(islandora_gis_geoserver_base_maps_load($object) as $index => $base_map_layer) {

      dpm('trace3');
      dpm($base_map_layer);
      
      if ($base_map_layer) {
	  
	//$layers[$index] = $base_map_layer;
	$layers[] = $base_map_layer;
      }
    }
  }

  dpm('trace6');
  dpm($layers);
  return $layers;
}
