<?php

  /**
   * @file Hook implementations for integrating with the openlayers Module
   * @author griffinj@lafayette.edu
   *
   */

/**
 * Implements hook_openlayers_layers().
 */
function _islandora_gis_geoserver_openlayers_layers() {

  $ol_layers = array();
  //$gs_layers = geoserver_layers_load();
  $gs_layers = islandora_gis_geoserver_layers_load();
  $url = geoserver_url();
  $workspace = variable_get('geoserver_workspace', '');

  $workspace = empty($workspace) ? 'default' : $workspace;
  $url .= $workspace . '/';

  foreach (array_values($gs_layers) as $gs_layer) {

    if (!isset($gs_layer->data['openlayers'])) {
      // Layer has no OpenLayers configuration --> skip the layer
      continue;
    }
    $openlayers = $gs_layer->data['openlayers'];

    if ($openlayers['wms']) {
    
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
						),
			      'options' => array('TRANSPARENT' => 'true',
						 'exceptions' => 'application/vnd.ogc.se_inimage',
						 'format' => 'image/png',
						 //'layers' => array($workspace . ':' . $gs_layer->name),
						 'layers' => array($workspace . ':' . 'eapl-sanborn-easton-1919_008-011+014-016_modified'),
						 //'layers' => array($workspace . ':' . 'easton_pa_ne'),
						 'styles' => '',
						 //'numZoomLevels' => 36 // Zoom levels
						 ),
			      'numZoomLevels' => 36, // Zoom levels
			      'maxResolution' => 0.17578125,
			      );
      dpm($ol_layer);

      //$ol_layers[$ol_layer->name] = $ol_layer;
      $ol_layers[] = array('layer' => $ol_layer,
			   'callback' => '_islandora_gis_layers_process');
    }
  }

  return $ol_layers;
  }
