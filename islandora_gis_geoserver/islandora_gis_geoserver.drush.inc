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
	'examples' => array('Standard example' => 'drush islandora-geoserver-link-maps lebaneseTown:shapefile1 lebaneseTown:rasterMap1 admin secret', // @todo Implement
			    'Complex example' => ''), // @todo Implement
	'aliases' => array()
	);

  return $items;
}

/**
 * For ingestion using a drush command
 * @param string $file_path The file system path to the compressed Esri Shapefile
 *
 */
function drush_islandora_gis_geoserver_islandora_geoserver_link_maps($object_id, $map_object_id, $user, $pass, $url = NULL) {

  // Instantiate the connection as the admin user
  $connection = islandora_get_tuque_connection(user_load(1));
  $session = new IslandoraSession($connection);

  $url = is_null($url) ? geoserver_url() : $url;

  $geoserver_session = new IslandoraGeoServer\IslandoraGeoServerSession($user, $pass, $url);

  $shapefile = new IslandoraShapefile($session, $geoserver_session, $object_id);
  drush_log('testing', 'success');

  $workspace = variable_get('geoserver_workspace', '');

  // Work-around for empty strings (default)
  // @todo Refactor
  $workspace = empty($workspace) ? 'default' : $workspace;


  $map = new IslandoraGeoImage($session, $geoserver_session, $map_object_id, $workspace);


  $map->add_shapefile($shapefile);
  drush_log(dt('Successfully added the Shapefile Object ' . $object_id . ' as a dependent of the GeoTIFF base map ' . $map_object_id), 'success');
}
