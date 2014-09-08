<?php

  /**
   * @file Drush hook implementations
   * @author griffinj@lafayette.edu
   *
   */

  /**
   * Implements hook_drush_help().
   * @param string $command the command being referenced
   * @returns string a description of the command
   *
   */
function islandora_gis_geoserver_drush_help($command) {

  switch($command) {
    
  case 'islandora-geoserver-link-maps':
    
    return dt('Ingest a compressed Esri Shapefile');
    break;
  default:
    
    break;
  }
  }

/**
 * Implements hook_drush_command().
 * @returns array an array of commands
 *
 */
function islandora_gis_geoserver_drush_command() {

  $items = array();

  $items['islandora-geoserver-link-maps'] =
  array('description' => dt('Link GeoTIFF maps (Islandora Large Image Objects) to a Shapefile (Islandora Shapefile Object)'),
	'arguments' => array('arg1' => dt(''), // @todo Implement
			     ),
	'examples' => array('Standard example' => '', // @todo Implement
			    'Complex example' => ''), // @todo Implement
	'aliases' => array()
	);

  return $items;
}

$module_path = drupal_get_path('module', 'islandora_dss');
include_once "$module_path/libraries/Islandora.php";

$module_path = drupal_get_path('module', 'islandora_gis_geoserver');
include_once "$module_path/libraries/IslandoraGeoServerClient.php";
include_once "$module_path/libraries/Shapefile.php";

/**
 * For ingestion using a drush command
 * @param string $file_path The file system path to the compressed Esri Shapefile
 *
 */
function drush_islandora_gis_geoserver_islandora_geoserver_link_maps($object_id, $map_object_id, $user, $pass, $url = 'http://rhodes0.stage.lafayette.edu:8080/geoserver-sqlite-2.5') {

  // Instantiate the connection as the admin user
  $connection = islandora_get_tuque_connection(user_load(1));

  $session = new IslandoraSession($connection);
  $geoserver_session = new IslandoraGeoServer\IslandoraGeoServerSession($user, $pass, $url);
  $shapefile = new IslandoraShapefile($session, $geoserver_session, $object_id);

  $map = new IslandoraGeoImage($session, $geoserver_session, $map_object_id);
  $map->add_shapefile($shapefile);
  drush_log(dt('Successfully added the Shapefile Object ' . $object_id . ' as a dependent of the GeoTIFF base map ' . $map_object_id), 'success');

}
