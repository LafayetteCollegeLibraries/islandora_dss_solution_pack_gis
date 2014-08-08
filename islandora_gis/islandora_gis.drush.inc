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
function islandora_gis_drush_help($command) {

  switch($command) {
    
    case 'islandora-gis-ingest':
    
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
function islandora_gis_drush_command() {

  $items = array();

  $items['islandora-gis-ingest'] =
  array('description' => dt('Ingest a compressed Esri Shapefile'),
	'arguments' => array('arg1' => dt('File system path to the compressed Shapefile'),
			     'arg2' => dt('The Islandora Object namespace'),
			     'arg3' => dt('The Islandora Object PID'),
			     'arg4' => dt('The Islandora Object label'),
			     'arg5' => dt('The Islandora Collection for the Object')
			     ),
	'examples' => array('Standard example' => 'drush islandora-gis-ingest /opt/esri_store/shapefile.zip',
			    'Complex example' => 'drush islandora-gis-ingest ~/Downloads/GlendaleAZ_Council_Districts.zip gis gis:glendale "Glendale Council Districts"'),
	'aliases' => array()
	);

  return $items;
}

/**
 * For ingestion using a drush command
 * @param string $file_path The file system path to the compressed Esri Shapefile
 *
 */
function drush_islandora_gis_ingest($file_path, $namespace = NULL, $pid = NULL, $label = 'New Shapefile Object', $collections = array('islandora:sp_shapefile_collection')) {

  module_load_include('inc', 'islandora', 'includes/utilities');

  $shapefile_zip = file_save_data(file_get_contents($file_path), 'temporary://islandora_gis_shapefile_' . basename($file_path));

  // Instantiate the connection as the admin user
  $connection = islandora_get_tuque_connection(user_load(1));

  // ID is more specific than namespace so it will take precedence.
  $id = $namespace ?: 'islandora';
  $id = $pid ?: $id;
  
  $datastreams = array();

  // For appending the SHP datastream
  $datastreams[] = array('dsid' => 'SHP',
			 'label' => $label . ' (Compressed Esri Shapefile)',
			 'mimetype' => 'application/zip',
			 'datastream_file' => $shapefile_zip->uri);

  $content_models = array();

  // Set the Content Model
  $content_models[] = 'islandora:sp_shapefile_cmodel';

  // Set the relationships for the RELS-EXT Datastream
  $relationship_map = function($o) {
    return array('relationship' => 'isMemberOfCollection', 'pid' => $o);
  };
  $relationships = empty($collections) ? array() : array_map($relationship_map, $collections);

  $object = islandora_prepare_new_object($id, $label, $datastreams, $content_models, $relationships);
  try {

    islandora_add_object($object);
    drush_log(dt(basename($shapefile_zip->uri) . " was successfully ingested into " . $object->id), 'success');
  } catch (Exception $e) {

    drush_log(dt($shapefile_zip->uri . " could not be ingested:" . $e->getMessage()), 'error');
  }

  // Delete the file
  if(!file_delete($shapefile_zip)) {

    drush_log(dt($shapefile_zip->uri . " could not be deleted."), 'warning');
  }
}
