<?php

  /**
   * @file Hook implementations for integrating with the openlayers Module
   * @author griffinj@lafayette.edu
   *
   */
function islandora_gis_geoserver_openlayers_title($gs_layer_title) {

  $ol_layer_title = $gs_layer_title;

  $default_layer_names = array('States_CottonSeedMills_1860' => 'Mills by state, 1860',
			       'States_CottonSeedMills_1880' => 'Mills by state, 1880',
			       'States_CottonSeedMills_1900' => 'Mills by state, 1900',
			       'US_county_cotton_1860' => 'Mills by county, 1860',
			       'US_county_cotton_1880' => 'Mills by county, 1880',
			       'US_county_cotton_1900' => 'Mills by county, 1900',
			       'pureFood_7' => 'US_state_1860_WGS84_simple',
			       );
  $layer_names = variable_get('islandora_gis_geoserver_openlayers_layer_names', $default_layer_names);

  if(array_key_exists($gs_layer_title, $layer_names)) {

    $ol_layer_title = $default_layer_names[$gs_layer_title];
  }

  return $ol_layer_title;

  }

/**
 * Implements hook_openlayers_layers().
 *
 */
function _islandora_gis_geoserver_openlayers_layers($wfs_layer_count = 1) {

  $ol_layers = array();
  $gs_layers = islandora_gis_geoserver_layers_load();
  $url = geoserver_url();
  $workspace = variable_get('geoserver_workspace', '');

  $workspace = empty($workspace) ? 'default' : $workspace;
  $url .= $workspace . '/';

  // Ensures that, if there are multiple WFS layers, all shall be disabled by default
  // Provides a work-around for GEO-52

  /*
  $wfs_layer_count = count(array_filter(array_values($gs_layers), function($gs_layer) {

	return array_key_exists('wfs', $gs_layer->data['openlayers']);
      } ));
  */

  foreach (array_values($gs_layers) as $gs_layer) {

    if (!isset($gs_layer->data['openlayers'])) {

      // Layer has no OpenLayers configuration --> skip the layer
      continue;
    }

    $openlayers = $gs_layer->data['openlayers'];

    if(array_key_exists('wms', $openlayers)) {
    
      $data = isset($openlayers['wms_data']) ? $openlayers['wms_data'] : array();
      $ol_layer = new stdClass();
      $ol_layer->api_version = 1;
      $ol_layer->name = $gs_layer->name . '_wms';
      $ol_layer->title = $gs_layer->title;
      $ol_layer->description = t('Untiled WMS layer of GeoServer layer !name',
				 array('!name' => $gs_layer->name));

      $ol_layer->data = array('layer_type' => 'openlayers_layer_type_wms',
			      'geoserver_wms' => TRUE,
			      'base_url' => $url . 'wms',
			      'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
			      'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:900913', 'EPSG:3857'),
			      'params' => array('isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
						'buffer' => '2',
						'ratio' => '1.5',
						'singleTile' => 1,
						'maxResolution' => $data['maxResolution'],
						'maxExtent' => $data['maxExtent']
						),
			      'options' => array('transparent' => 'true',
						 'exceptions' => 'application/vnd.ogc.se_inimage',
						 'format' => 'image/png',
						 'layers' => array($workspace . ':' . $gs_layer->name),
						 'styles' => '',
						 'time' => '' // For time integration
						 ),
			      'visibility' => TRUE, // Forced for multiple raster layers
			      );

      $ol_layers[] = array('layer' => $ol_layer,
			   'callback' => '_islandora_gis_layers_process');

    } elseif(array_key_exists('wfs', $openlayers)) {

      $data = isset($openlayers['wfs_data']) ? $openlayers['wfs_data'] : array();
      $ol_layer = new stdClass();
      $ol_layer->api_version = 1;
      $ol_layer->name = $gs_layer->name . '_wfs';

      $ol_layer->title = islandora_gis_geoserver_openlayers_title($gs_layer->title);

      $ol_layer->description = t('WFS layer of GeoServer layer !name',
				 array('!name' => '(GeoServer)'));

      // Extending this to providing a mapping between web feature names and Islandora Objects
      //! @todo Refactor
      $default_object_feature_map = array('pureFood_1' => 'States_CottonSeedMills_1860_WGS84_simple_2',
					  'pureFood_2' => 'States_CottonSeedMills_1880_WGS84_simple_2',
					  'pureFood_3' => 'States_CottonSeedMills_1900_WGS84_simple_2',
					  'pureFood_4' => 'US_county_cotton_1860_WGS84_simple_3',
					  'pureFood_5' => 'US_county_cotton_1880_WGS84_simple_2',
					  'pureFood_6' => 'US_county_cotton_1900_WGS84_simple_2',
					  'pureFood_7' => 'US_state_1860_WGS84_simple',
					  );

      $object_feature_map = variable_get('islandora_gis_geoserver_openlayers_feature_map', $default_object_feature_map);
      
      $typeName = array_key_exists($gs_layer->name, $object_feature_map) ? $object_feature_map[$gs_layer->name] : $gs_layer->name;

      $ol_layer->data = array('attribution' =>  isset($gs_layer->data['attribution']) ? $gs_layer->data['attribution'] : NULL,
			      'layer_type' => 'openlayers_layer_type_geoserver_wfs',
			      'sld' => '',
			      'url' => $url . 'wfs',
			      'typeName' => $typeName,
			      'strategy' => isset($data['strategy']) ? $data['strategy'] : 'bbox',
			      'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
			      'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:4326'),
			      'visibility' => $wfs_layer_count == 1 // Ensures that, for cases in which multiple WFS layers are available, all are disabled by default
			      );

      $ol_layers[] = array('layer' => $ol_layer,
			   'callback' => '_islandora_gis_layers_process');
    }
  }

  return $ol_layers;
}
