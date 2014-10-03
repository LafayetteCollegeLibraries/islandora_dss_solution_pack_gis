<?php

  /**
   * @file Hook implementations for integrating with the openlayers Module
   * @author griffinj@lafayette.edu
   *
   */

/**
 * Implements hook_openlayers_layers().
 */
function _islandora_gis_geoserver_openlayers_layers($wfs_layer_count = 1) {

  $ol_layers = array();
  //$gs_layers = geoserver_layers_load();
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

  //dpm($wfs_layer_count);

  foreach (array_values($gs_layers) as $gs_layer) {

    if (!isset($gs_layer->data['openlayers'])) {
      // Layer has no OpenLayers configuration --> skip the layer
      continue;
    }
    $openlayers = $gs_layer->data['openlayers'];

    //if ($openlayers['wms']) {
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
			      //'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:4326'),
			      //'projection' => array('EPSG:2272'),
			      //'projection' => array('EPSG:4326'),
			      'params' => array('isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
						'buffer' => '2',
						'ratio' => '1.5',
						'singleTile' => 1,
						),
			      'options' => array('TRANSPARENT' => 'true',
						 'exceptions' => 'application/vnd.ogc.se_inimage',
						 'format' => 'image/png',
						 'layers' => array($workspace . ':' . $gs_layer->name),
						 //'layers' => array($workspace . ':' . 'eapl-sanborn-easton-1919_008-011+014-016_modified_v5'),
						 //'layers' => array($workspace . ':' . 'eapl-sanborn-easton-1919_008-011+014-016_modified_v5_epsg_4326'),
						 //'layers' => array($workspace . ':' . 'eapl-sanborn-easton-1919_008-011+014-016_modified_v5_epsg_3857'),
						 //'layers' => array($workspace . ':' . ''),
						 'styles' => '',
						 //'numZoomLevels' => 36 // Zoom levels
						 //'numZoomLevels' => 60 // Zoom levels
						 ),
			      // @author griffinj
			      // This must be overridden either at the layer of the map or within the JavaScript
			      //
			      //'numZoomLevels' => 36, // Zoom levels
			      'numZoomLevels' => 60, // Zoom levels

			      //'maxResolution' => 0.17578125, // This must be generated using the bounding box,
			      'maxResolution' => 0.3, // This must be generated using the bounding box,
			      'maxExtent' => array(-8373330.315, 4965998.047, -8371641.131, 4967177.293) // Specific to the projection
			      );

      dpm($ol_layer);

      //$ol_layers[$ol_layer->name] = $ol_layer;
      $ol_layers[] = array('layer' => $ol_layer,
			   'callback' => '_islandora_gis_layers_process');

    //} elseif($openlayers['wfs']) {
    } elseif(array_key_exists('wfs', $openlayers)) {

      /*
	if ($openlayers['wfs']) {
      $style = $gs_layer->data['style'];
      $sld = isset($gs_styles[$style]) ? $gs_styles[$style]->data : '';
      $data = isset($openlayers['wfs_data']) ? $openlayers['wfs_data'] : array();
      $ol_layer = new stdClass();
      $ol_layer->api_version = 1;
      $ol_layer->name = $gs_layer->name . '_wfs';
      $ol_layer->title = $gs_layer->title;
      $ol_layer->description = t('WFS layer of GeoServer layer !name',
          array('!name' => $gs_layer->name));
      $ol_layer->data = array(
        'attribution' =>  isset($gs_layer->data['attribution']) ? $gs_layer->data['attribution'] : NULL,
        'layer_type' => 'openlayers_layer_type_geoserver_wfs',
        'sld' => $sld,
        'url' => $url . $workspace . '/ows',
        'typeName' => $workspace . ':' . $gs_layer->name,
        'strategy' => isset($data['strategy']) ? $data['strategy'] : 'bbox',
        'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
        'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:900913', 'EPSG:3857'),
      );
      $ol_layers[$ol_layer->name] = $ol_layer;
    }
      */

      // geoserver/plugins/openlayers/layer_types/openlayers_layer_type_geoserver_wfs.inc
      //$style = $gs_layer->data['style'];
      //$sld = isset($gs_styles[$style]) ? $gs_styles[$style]->data : '';
      $data = isset($openlayers['wfs_data']) ? $openlayers['wfs_data'] : array();
      $ol_layer = new stdClass();
      $ol_layer->api_version = 1;
      $ol_layer->name = $gs_layer->name . '_wfs';
      $ol_layer->title = $gs_layer->title;
      $ol_layer->description = t('WFS layer of GeoServer layer !name',
				 //array('!name' => $gs_layer->name . ' (GeoServer)'));
				 array('!name' => '(GeoServer)'));

      $ol_layer->data = array('attribution' =>  isset($gs_layer->data['attribution']) ? $gs_layer->data['attribution'] : NULL,
			      'layer_type' => 'openlayers_layer_type_geoserver_wfs',
			      'sld' => '',
			      //'url' => $url . $workspace . '/ows',
			      'url' => $url . 'wfs',
			      'typeName' => $workspace . ':' . $gs_layer->name,
			      //'typeName' => 'ExamplePoints_b_v1_prj',
			      
			      //'typeName' => 'States_CottonSeedMills_1860_WGS84',
			      //'typeName' => 'States_CottonSeedMills_1880_WGS84',
			      //'typeName' => 'States_CottonSeedMills_1900_WGS84',
			      //'typeName' => 'US_county_cotton_1860_WGS84',
			      //'typeName' => 'US_county_cotton_1880_WGS84',
			      //'typeName' => 'US_county_cotton_1900_WGS84',

			      //'typeName' => 'States_CottonSeedMills_1860_WGS84_simple',
			      //'typeName' => 'States_CottonSeedMills_1880_WGS84_simple',
			      //'typeName' => 'States_CottonSeedMills_1900_WGS84_simple',

			      //'typeName' => 'US_county_cotton_1860_WGS84_simple',
			      //'typeName' => 'US_county_cotton_1880_WGS84_simple',
			      //'typeName' => 'US_county_cotton_1900_WGS84_simple',

			      //'typeName' => 'US_state_1860_WGS84_simple',

			      'strategy' => isset($data['strategy']) ? $data['strategy'] : 'bbox',
			      'isBaseLayer' => isset($data['isBaseLayer']) ? $data['isBaseLayer'] : FALSE,
			      //'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:900913', 'EPSG:3857'),
			      'projection' => isset($data['projection']) ? $data['projection'] : array('EPSG:4326'),

			      'visibility' => $wfs_layer_count == 1 // Ensures that, for cases in which multiple WFS layers are available, all are disabled by default
			      );

      //dpm($ol_layer);

      $ol_layers[] = array('layer' => $ol_layer,
			   'callback' => '_islandora_gis_layers_process');
    }
  }

  return $ol_layers;
  }
