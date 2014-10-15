<?php

  /**
   * @file Theming hook implementations
   * @author griffinj@lafayette.edu
   *
   */

/**
 * Implements hook_preprocess().
 *
 * @param array $variables
 *   an array of variables that will be passed to the theme function
 */
function islandora_gis_preprocess_islandora_gis(&$variables) {

  drupal_add_js('misc/form.js');
  drupal_add_js('misc/collapse.js');
  $islandora_object = $variables['islandora_object'];
  $repository = $islandora_object->repository;
  module_load_include('inc', 'islandora', 'includes/datastream');
  module_load_include('inc', 'islandora', 'includes/utilities');
  // Get DC metadata.
  try {
    $dc = $islandora_object['DC']->content;
    $dc_object = DublinCore::importFromXMLString($dc);
  } catch (Exception $e) {
    drupal_set_message(t('Error retrieving object %s %t', array('%s' => $islandora_object->id, '%t' => $e->getMessage())), 'error', FALSE);
  }
  $variables['islandora_dublin_core'] = isset($dc_object) ? $dc_object : NULL;
  $variables['dc_array'] = isset($dc_object) ? $dc_object->asArray() : array();
  $variables['islandora_object_label'] = $islandora_object->label;
  $variables['theme_hook_suggestions'][] = 'islandora_gis__' . str_replace(':', '_', $islandora_object->id);
  $variables['parent_collections'] = islandora_get_parents_from_rels_ext($islandora_object);
}

/**
 * Implements template_preprocess_HOOK().
 */
function islandora_gis_preprocess_islandora_basic_collection_wrapper(&$vars) {

  $islandora_feature_map = variable_get('islandora_gis_geoserver_islandora_feature_map', array());

  dpm($islandora_feature_map);
  dpm($vars);
}

function islandora_gis_preprocess_islandora_gis_popup(&$vars) {

  $islandora_object = $vars['islandora_object'];
  $vars['islandora_object_tn'] = theme_image(array('path' => '/islandora/object/' . $islandora_object->id . '/datastream/TN/view',
						   'alt' => $islandora_object->label,
						   'title' => $islandora_object->label,
						   'attributes' => array()));

}

/**
 * Theming functionality for individual OpenLayers Map widgets
 * Implements hook_preprocess_HOOK().
 *
 */
function islandora_gis_preprocess_openlayers_map(&$vars) {

  dpm($vars);

  drupal_add_css(drupal_get_path('module', 'islandora_gis') . '/css/islandora_gis.css');

  // Retrieve the mapping between feature ID's and Islandora Objects from the existing session  
  $islandora_feature_map = variable_get('islandora_gis_geoserver_islandora_feature_map', array());

  foreach($islandora_feature_map as $feature_id => &$value) {

    $object = islandora_object_load($value);

    //! @todo Provide an interface through which to specify the Dublin Core elements by XPath (along with labels and whether or not the CDATA should be rendered as a link to the Object)
    $dc_element_map = variable_get('islandora_gis_popup_dc_elements', array('//dc:title' => array('label' => 'Title',
												  'href' => true),
									    '//dc:coverage' => array('label' => 'Coverage',
												     'href' => false),
									    ));

    $dc_elements = array();

    $dc_doc = new SimpleXMLElement($object['DC']->content);
    $dc_doc->registerXPathNamespace('http://purl.org/dc/elements/1.1/', 'dc');
    foreach($dc_element_map as $xpath => $value) {

      $elements = $dc_doc->xpath($xpath);
      $element = array_shift($elements);

      // Provide a link to the Objects for element values specified as "href" = TRUE
      if($value['href']) {

	$dc_elements[] = array('label' => $value['label'],
			       'value' => l((string) $element, 'islandora/object/' . $object->id),
			       'class' => drupal_strtolower(preg_replace('/[^A-Za-z0-9]/', '-', 'dc:' . $value['label']))
			       );
      } else {

	$dc_elements[] = array('label' => $value['label'],
			       'value' => (string) $element,
			       'class' => drupal_strtolower(preg_replace('/[^A-Za-z0-9]/', '-', 'dc:' . $value['label']))
			       );
      }
    }

    // Theme the output for the OpenLayers Popup
    $value = theme('islandora_gis_popup', array('islandora_object' => $object, 'islandora_metadata_fields' => $dc_elements));
  }

  /**
   * Provide the default zoom extent for the map based upon the WMS Islandora layers
   *
   */
  $default_zoom_extent = variable_get('islandora_gis_openlayers_default_zoom_extent', array());
  dpm($default_zoom_extent);

  if(empty($default_zoom_extent)) {
    
    /*
    $wms_layers = array_filter($vars['map']['layers'], function(&$layer) {
	
	return $layer['layer_type'] == 'openlayers_layer_type_wms';
      });
    */
    $wms_layers = array();
    foreach($vars['map']['layers'] as &$layer) {

      if($layer['layer_type'] == 'openlayers_layer_type_wms') {

	$wms_layers[] = $layer;
      }
    }
    
    /*
    $wms_layer_extents = array_map(function($layer) {
	
	return $layer['params']['maxExtent'];
      }, $wms_layers);
    */
    $wms_layer_extents = array();
    foreach($wms_layers as &$layer) {

      $wms_layer_extents[] = $layer['params']['maxExtent'];
      $layer['params']['maxExtent'] = array(-180, -90, 180, 90);
    }

    $default_zoom_extent = array_reduce($wms_layer_extents, function($u, $v) {
	
	$u = empty($u) ? $v : $u;
	foreach($v as $i => $value) {
	  
	  $u[$i] += $value - (float) $u[$i];
	}
	
	return $u;
      });
  }
  dpm($default_zoom_extent);

  // Reduce
  $default_zoom_extent = array(-8373859.3930893,
			       4966232.0909636,
			       -8371064.6642431,
			       4967187.553817,
			       );

  dpm($default_zoom_extent);

  $js = array('islandoraGis' => array('features' => $islandora_feature_map,
				      'defaultZoomExtent' => $default_zoom_extent
				      ));
  drupal_add_js($js, 'setting');

}
