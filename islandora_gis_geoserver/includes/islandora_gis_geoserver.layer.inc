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

function islandora_gis_geoserver_base_maps_load($object) {

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

  $layers = array();
  foreach($connection->repository->ri->query($query_array['query'], $query_array['type']) as $result) {

    $pid = $result['object']['value'];
    $coverage_name = preg_replace('/\:/', '_', $pid);

    $layer = new stdClass();
    $layer->name = $coverage_name;
    $layer->title = $result['title']['value'];

    $layer->data = array('openlayers' => array('wms' => TRUE,
					       'wms_data' => array('isBaseLayer' => TRUE,
								   'projection' => array('EPSG:900913', 'EPSG:3857'))));
    $layers[] = $layer;
  }

  return $layers;
}

/**
 * For generating geoserver layers using an Islandora Shapefile Object
 * @see geoserver_layers_load()
 *
 */
function islandora_gis_geoserver_layers_load() {

  /*
  ctools_include('export');

  if ($reset) {
    ctools_export_load_object_reset('geoserver_layers');
  }


  */
  //$layers = ctools_export_load_object('geoserver_layers', 'all', array());

  $layers = array();
  $object_pid = variable_get('islandora_gis_view_object_pid', '');
  if(!empty($object_pid)) {

    //$object = islandora_object_load($object_pid);

    $session = islandora_dss_get_session();
    $geoserver_session = islandora_gis_geoserver_get_session();
    $object = islandora_object_load($object_pid);

    /*
    try {

      $object = new IslandoraShapefile($session, $geoserver_session, $object_pid, $object);
      //$map_objects = $object->get_base_maps();

      foreach ($object->base_maps as $index => $base_map) {

	$layer_object = $base_map->to_layer();
	if ($layer_object) {

	  $layers[$index] = $layer_object;
	}
      }
    } catch(Exception $e) {

      // Log the warning
      // @todo Implement

      // Attempt to retrieve the layers manually
      foreach(islandora_gis_geoserver_base_maps_load($object) as $index => $base_map_layer) {
	
	if ($base_map_layer) {
	  
	  $layers[$index] = $base_map_layer;
	}
      }
    }
    */

    // Log the warning
    // @todo Implement

    // Attempt to retrieve the layers manually
    foreach(islandora_gis_geoserver_base_maps_load($object) as $index => $base_map_layer) {
	
      if ($base_map_layer) {
	  
	$layers[$index] = $base_map_layer;
      }
    }
  }

  return $layers;
}
