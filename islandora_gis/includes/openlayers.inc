<?php

  /**
   * @file Core functionality integrated with the openlayers Module
   * @author griffinj@lafayette.edu
   *
   */

/**
 * Instantiating a Layer from the KML Datastream content
 *
 * @param FedoraObject $object the Islandora Object containing the KML datastream
 * @returns stdClass the Layer Object
 *
 */
function _islandora_gis_layer_load_kml($object) {

  $layer_title = $object->label;
  $layer_name = preg_replace('/\s/', '_', strtolower($layer_title));

  $kml_data = '<?xml version="1.0" encoding="UTF-8"?>' . $object['KML']->content;

  /* Example with KML layer */
  $layer = new stdClass();
  $layer->disabled = FALSE; /* Edit this to true to make a default openlayers_layers disabled initially */
  $layer->api_version = 1;
  $layer->name = $layer_name;
  $layer->title = $layer_title;
  $layer->description = $layer_title; //@todo Refactor and pass a Dublin Core or MODS XPath text node
  $layer->data = array(
    'method' => 'raw',
    'raw' => $kml_data,
    'formatOptions' => array(
      'extractStyles' => TRUE,
      'extractTracks' => FALSE,
      'extractAttributes' => TRUE,
    ),
    'projection' => array(
      'EPSG:4326',
    ),
    'isBaseLayer' => FALSE,
    'layer_type' => 'openlayers_layer_type_kml',
    'layer_handler' => 'kml',
    'vector' => TRUE,
    'title' => $layer_title, //@todo Resolve by integrating with the class
    'url' => url('islandora/object/' . $object->id . '/datastream/KML/view', array('absolute' => TRUE)) //@todo Refactor
  );

  return $layer;
}

/**
 * Generate a layer from the JSON Datastream content within an Islandora Object
 * @param FedoraObject $object the Islandora Object containing the KML datastream
 * @returns stdClass the Layer Object
 */
function _islandora_gis_layer_load_geojson($object) {

  $layer_title = $object->label;
  $layer_name = preg_replace('/\s/', '_', strtolower($layer_title));

  $geojson_data = $object['JSON']->content;
  
  // GeoJSON example with direct data
  $layer = new stdClass();
  $layer->api_version = 1;
  $layer->name = $layer_name;
  $layer->title = $layer_title;
  $layer->description = $layer_title; //@todo Refactor and pass a Dublin Core or MODS XPath text node
  $layer->data = array('resolutions' => openlayers_get_resolutions('EPSG:900913'),
		       'serverResolutions' => openlayers_get_resolutions('EPSG:4326'),
		       'layer_type' => 'openlayers_layer_type_geojson',
		       'layer_handler' => 'geojson',
		       'projection' => array('EPSG:4326'),
		       'isBaseLayer' => FALSE,
		       'vector' => TRUE,
		       'geojson_data' => $geojson_data,
		       'title' => $layer_title, //@todo Resolve by integrating with the class
		       'url' => url('islandora/object/' . $object->id . '/datastream/JSON/view', array('absolute' => TRUE)) //@todo Refactor
		       );

  return $layer;
}

/**
 * Load a Layer for openlayers
 * @see openlayers_layer_load
 *
 * @param $ds_id
 *   Datastream ID for the content containing the layer
 * @param $object
 *   FedoraObject containing the datastreams for the layer
 * @param $reset
 *   Boolean whether to reset cache or not
 * @return openlayers_layer_type|FALSE
 *   Layer export
 */
function islandora_gis_layer_load($ds_id, $objects, $reset = FALSE) {

  $output = array();

  foreach($objects as $object) {

    if(isset($object[$ds_id])) {

      // Default to KML
      switch($ds_id) {

      case 'JSON':

	//return _islandora_gis_layer_load_geojson($object);
	$islandora_layer = _islandora_gis_layer_load_geojson($object);
	break;
      case 'KML':
      default:

	//return _islandora_gis_layer_load_kml($object);
	$islandora_layer = _islandora_gis_layer_load_kml($object);
      }

      if(module_exists('islandora_gis_geoserver')) {

	$islandora_layer->data['max_extent'] = islandora_gis_geoserver_max_extent($object);
      }

      $output[] = array('layer' => $islandora_layer,
			'callback' => '_islandora_gis_layers_process');
    }
  }

  return $output;
}

/**
 * Generate an OpenLayers Map Object from an Islandora Object
 * @param FedoraObject $object
 *
 */
function islandora_gis_map_load($objects, $map_title, $map_desc_xpath = NULL) {

  //$map_title = $object->label;
  $map_name = preg_replace('/\s/', '_', strtolower($map_title));
  
  if($map_desc_xpath) {

    /**
     * @todo Implement functionality for mapping an individual MODS Document XPath for the description of the map
     *
     */
    $map_description = $map_title;
  } else {

    $map_description = $map_title;
  }

  /**
   * Default to the MapQuest OpenStreetMap WFS
   * @todo Abstract and integrate with the administrative UI for the configuration of default base maps
   * @todo Integrate with GeoServer for the addition of raster base maps
   */
  $openlayers_maps = new stdClass();
  $openlayers_maps->disabled = FALSE; /* Edit this to true to make a default openlayers_maps disabled initially */
  $openlayers_maps->api_version = 1;
  $openlayers_maps->name = $map_name;
  $openlayers_maps->title = $map_title;
  $openlayers_maps->description = $map_description;

  $image_path = drupal_get_path('module', 'openlayers') . '/themes/default_dark/img/';
  $css_path = drupal_get_path('module', 'openlayers') . '/themes/default_dark/style.css';

  $center = array('initial' => array('centerpoint' => '0,0',
				     'zoom' => '2',
				     ),
		  'restrict' => array('restrictextent' => 0,
				      'restrictedExtent' => '',
				      ),
		  );

  $behaviors = array('openlayers_behavior_keyboarddefaults' => array(),
		     'openlayers_behavior_layerswitcher' => array('ascending' => 1,
								 'sortBaseLayer' => '0',
								 'roundedCorner' => 1,
								 'roundedCornerColor' => '#222222',
								 'maximizeDefault' => 0,
								  ),
		     'openlayers_behavior_navigation' => array('zoomWheelEnabled' => 1,
							       'zoomBoxEnabled' => 1,
							       'documentDrag' => 0,
							       ),
		     'openlayers_behavior_panzoombar' => array('zoomWorldIcon' => 0,
							       'panIcons' => 1,
							       ),
		     'openlayers_behavior_popup' => array('layers' => array('islandora_layer' => 'islandora_layer',
									   ),
							  'panMapIfOutOfView' => 0,
							  'keepInMap' => 1,
							  ),
		     'openlayers_behavior_zoomtolayer' => array('zoomtolayer' => array('islandora_layer' => 'islandora_layer',
										       ),
								'point_zoom_level' => '5',
								'zoomtolayer_scale' => '1',
								),
		     );

  $layers = array('google_normal' => 'google_normal',
		  'google_satellite' => 'google_satellite',
		  );

  // Load the layer from the Islandora Object
  //$islandora_layers = islandora_gis_layer_load(variable_get('islandora_gis_default_openlayers_ds_id', 'JSON'), $objects);

  // Load the default layer based upon the project
  $default_layer = variable_get('islandora_gis_openlayers_default_layer', 'google_normal');

  $openlayers_maps->data = array('width' => 'auto',
				 'height' => '400px',
				 'image_path' => $image_path,
				 'css_path' => $css_path,
				 'proxy_host' => '',
				 'hide_empty_map' => 0,
				 'center' => $center,
				 'behaviors' => $behaviors,
				 'default_layer' => $default_layer,
				 'layers' => $layers,

				 //'layer_activated' => array(),

				 //'projection' => 'EPSG:3857',
				 'projection' => 'EPSG:4326',
				 'displayProjection' => 'EPSG:4326',
				 'styles' => array('default' => 'default',
						   'select' => 'default_select',
						   'temporary' => 'default',
						   ),
				 /*
				 'islandora_layers' => array(array('layer' => $islandora_layer,
				 'callback' => '_islandora_gis_layers_process')),
				 */
				 'islandora_layers' => array(),
				 //'islandora_layers' => $islandora_layers,
				 'minExtent' => array(-1, -1, 1, 1),
				 //'maxExtent' => $max_extent,
				 );

  /*
  if(module_exists('islandora_gis_geoserver')) {

    $openlayers_maps->data['max_extent'] = islandora_gis_geoserver_max_extent($object);
  }
  */

  return $openlayers_maps->data;
}

  /**
   * Prepare a map for rendering.
   *
   * Takes a map array and builds up the data given the
   * reference to objects like styles, layers, and behaviors.
   *
   * @ingroup openlayers_api
   *
   * @param $map
   *   Array of map settings
   * @return
   *   Filled in map array.
   */
function islandora_gis_build_map($map = array()) {

  // Get the necessary parts
  openlayers_include();
  module_load_include('inc', 'openlayers', 'includes/openlayers.render');
  module_load_include('inc', 'islandora_gis', 'includes/openlayers.render');

  // If no map is specified, use the default map.
  if (empty($map)) {
    if ($loaded_map = openlayers_map_load(
      variable_get('openlayers_default_map', 'default'))) {
      $map = $loaded_map->data;
    }
  }

  // Create ID for map as this will help with alters.
  $map['id'] = !isset($map['id']) ?
    _openlayers_create_map_id() : $map['id'];

  // Hook to alter map before main processing.  Styles, behaviors,
  // layers may all be added here.
  // hook_openlayers_map_preprocess_alter($map)
  drupal_alter('openlayers_map_preprocess', $map);

  // Styles and layer styles are not required parameters
  $map['styles'] = isset($map['styles']) ? $map['styles'] : array();
  $map['layer_styles'] = isset($map['layer_styles']) ? $map['layer_styles'] : array();
  $map['layer_styles_select'] = isset($map['layer_styles_select']) ? $map['layer_styles_select'] : array();
  $map['layer_styles_temporary'] = isset($map['layer_styles_temporary']) ? $map['layer_styles_temporary'] : array();

  // Process map parts.

  /*
  // This assumes that the Islandora Layer is always the last in the array
  $islandora_layer = array_pop($map['layers']);

  $map['islandora_layer'] = array('layer' => $islandora_layer,
				  'callback' => '_islandora_gis_layers_process');
  */

  $map['layers'] = _openlayers_layers_process($map['layers'], $map);
  //$map['layers'] = array_merge($map['layers'], _islandora_gis_layers_process(array($islandora_layer), $map));

  // Retrieve the Layer Object from the Islandora Layer
  foreach($map['islandora_layers'] as $index => $islandora_layer) {

    $layer = call_user_func($islandora_layer['callback'], array($islandora_layer['layer']), $map);

    // Work-around for layers bearing identical titles
    $layer_name = $layer['title'];
    if(array_key_exists($layer['title'], $map['layers'])) {
      
      //$map['layers'][$layer['title'] . "($index)"] = $layer;
      $layer_name = $layer['title'] . "_$index";
    }

    $map['layers'][$layer_name] = $layer;

    // Generated the activated layers from the "visibility" property
    if(!$layer['visibility']) {

      $map['layer_activated'][$layer_name] = FALSE;
    }
  }

  $map['behaviors'] = _openlayers_behaviors_render($map['behaviors'], $map);
  $map['styles'] = _openlayers_styles_process($map['styles'], $map['layer_styles'], $map['layer_styles_select'], $map['layer_styles_temporary'], $map);

  // Restrict map to its projection extent (data outwith cannot be represented).
  // Layer can additionally specfiy their maxExtent in case they use
  // non-default grids.

  $projection = openlayers_get_projection_by_identifier($map['projection']);

  /**
   * @author griffinj
   * Disabled in order to retrieve the bounding box
   *
   */
  //$map['maxExtent'] = $projection->getProjectedExtent();

  // In case the layer offers the same projection as the map, use this and do not provide
  // projection definition to client. Otherwise rely on the client to reproject on the fly.
  foreach ($map['layers'] as $layer_name => $layer) {

    if(in_array($map['projection'], $layer['projection'])){
      $map['layers'][$layer_name]['projection'] = $map['projection'];
    } else {

      // Client is able to reproject any possible projection because their definitions need to be
      // known to be able to set up a layer with a certain projection. Thus choice does not matter.
      $layerProjectionIdentifier = reset($layer['projection']);
      if($layerProjectionIdentifier===FALSE){
        throw new Exception(
          t('Layer !title lacks its projection. Please edit it to select a projection.', array(
              '!title' => $layer['title']
          ))
        );
      }
      $map['layers'][$layer_name]['projection'] = $layerProjectionIdentifier;

      //$map['layers'][$layer_name]['projection'] = NULL;
    }

    // Ensure JavaScript gets proper type.
    $map['layers'][$layer_name]['isBaseLayer'] = (boolean)($layer['isBaseLayer']);

    // griffinj
    $map['layers'][$layer_name]['numZoomLevels'] = 64;
    $map['layers'][$layer_name]['minResolution'] = '0.059716428337097';
  }

  // Hook to alter map one last time.  Final modification to existing
  // styles, behaviors, layers can happen here, but adding new styles,
  // behaviors will not get rendered.
  // hook_openlayers_map_alter($map)
  drupal_alter('openlayers_map', $map);

  // Check map for errors
  $map['errors'] = openlayers_error_check_map($map);
  return $map;
}

/**
 * Render map array
 *
 * Given a map array, render into HTML to display
 * a map.
 *
 * @ingroup openlayers_api
 *
 * @param $map
 *   Associative array of map paramters.
 * @return
 *   Map HTML.
 */
function islandora_gis_render_map_data($map = array()) {

  // Run map through build process
  $map = islandora_gis_build_map($map);

  $output = '';

  // Given hide_empty_map flag, check if the map has any features
  // defined. If not, assume it is an empty map and shouldn't be displayed.
  if (isset($map['hide_empty_map']) && $map['hide_empty_map'] == TRUE) {
    $empty = TRUE;
    foreach ($map['layers'] as $layer) {
      if (isset($layer['features']) && count($layer['features'])) {
        $empty = FALSE;
      }
    }
    if ($empty) {
      // Abort early because there are no features to display on the map anyway
      return '';
    }
  }

  // Currently the restricted extent of maps is always given in EPSG:3857 so
  // this projection needs to be available in the client for all restricted
  // maps. Using EPSG:4326 instead would likely be better.
  if(array_key_exists('restrict', $map['center']) && (boolean)$map['center']['restrict']['restrictextent']){

    /**
     * Need to assume an arbitrary projection of EPSG:4326
     * @todo Resolve with GeoJSON and OpenStreetMaps base maps
     *
     */
    /*
    openlayers_add_js_projection_definition(
      openlayers_get_projection_by_identifier('EPSG:3857')
    );
    */
    openlayers_add_js_projection_definition(
      openlayers_get_projection_by_identifier('EPSG:4326')
    );

  }

  // Return themed map if no errors found
  if (empty($map['errors'])) {

    // In case the layer offers the same projection as the map, use this and do not provide
    // projection definition to client. Otherwise rely on the client to reproject on the fly.

    foreach ($map['layers'] as $layer_name => $layer) {

      /**
       * Need to assume an arbitrary projection of EPSG:4326
       * @todo Resolve with GeoJSON and OpenStreetMaps base maps
       *
       */
      if(array_key_exists('projection', $map['layers'][$layer_name])) {
      
	//$map['layers'][$layer_name]['projection'] = 'EPSG:4326';
      }

        // Provide client with projection definition so that it can reproject
        openlayers_add_js_projection_definition(
          openlayers_get_projection_by_identifier($map['layers'][$layer_name]['projection'])
        );
    }

    // Ensure projections in use are known to the client (loads Proj4js if required)
    openlayers_add_js_projection_definition(openlayers_get_projection_by_identifier($map['projection']));
    openlayers_add_js_projection_definition(openlayers_get_projection_by_identifier($map['displayProjection']));

    $map['numZoomLevels'] = 64;

    $js = array('openlayers' => array('maps' => array($map['id'] => $map)));
    drupal_add_js($js, 'setting');

    // Push map through theme function and return
    $output = theme('openlayers_map', array(
      'map' => $map,
    ));
  }
  return $output;
}
