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
function islandora_gis_timeline_preprocess_islandora_gis(&$variables) {

  drupal_add_library('system','ui.slider');

  $islandora_object = $variables['islandora_object'];
  // Retrieve the date for the Islandora Object

  // Must comply with http://dublincore.org/documents/dcmi-period/
  // <dc:coverage><dc:temporal>PeriodOfTime

  $start_date = '';
  $end_date = '';

  // All Objects contain the Dublin Core datastream
  $dc_ds = $islandora_object['DC'];

  $dc_doc = new SimpleXMLElement($dc_ds->content);
  $dc_doc->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
  $dc_dates = $dc_doc->xpath('dc:date/dc:temporal');

  $site_start_date = variable_get('islandora_gis_timeline_start_date', '1860-01-01T00:00:00');
  $site_end_date = variable_get('islandora_gis_timeline_end_date', '1900-12-31T23:59:59');

  $start_date = new DateTime($site_start_date);
  $end_date = new DateTime($site_end_date);

  if(!empty($dc_dates)) {

    $dc_date = array_shift($dc_dates);
  
    // Handle the DCMI Period formatting
    if( preg_match('/start=(.+?); end=(.+?);/', (string) $dc_date, $dc_date_period_m) ) {

      $start_date_dcmi = $dc_date_period_m[1];
      $end_date_dcmi = $dc_date_period_m[2];

      $start_date = new DateTime($start_date_dcmi);
      $end_date = new DateTime($end_date_dcmi);
    }
  }

  // Use the PID or the URN info:fedora/islandora:pid?


  // Mapping is used to provide additional flexibility regarding the management of resources
  // @todo Abstract
  $gs_layer_key = islandora_gis_geoserver_openlayers_title($islandora_object->label);

  // For testing
  $end_date = new DateTime('1870-12-31T00:00:00');

  $objects = array($gs_layer_key => array('start' => $start_date->format('c'),
					  'end' => $end_date->format('c')
					  ));

  $element_selector = variable_get('islandora_gis_timeline_element_selector', 'timeline-container');
  $event_source_data = islandora_gis_timeline_event_source($islandora_object->id);

  $time_step = variable_get('islandora_gis_timeline_time_step', 315619200000);

  // Structure the date range for the Object, and pass it to the JavaScript
  drupal_add_js(array('islandoraGisTimeline' => array('objects' => $objects,
						      'elementSelector' => $element_selector,
						      'eventSourceData' => $event_source_data,
						      'timeStep' => $time_step,
						      'visibility' => FALSE,
						      'startDate' => $start_date->format('M d Y H:i:s \G\M\TO'),
						      'endDate' => $end_date->format('M d Y H:i:s \G\M\TO')
						      )), 'setting');

  // Integrate the timeline widget at the theming layer
  drupal_add_css(libraries_get_path('standalone-timeline') . '/dist/timeline.min.css');
  // ../../libraries/standalone-timeline/demo/styles.css
  drupal_add_css(libraries_get_path('standalone-timeline') . '/demo/styles.css');
  drupal_add_css(drupal_get_path('module', 'islandora_gis_timeline') . '/css/islandora_gis_timeline.css');

  drupal_add_js(libraries_get_path('standalone-timeline') . '/dist/timeline.min.js');
  drupal_add_js(libraries_get_path('standalone-timeline') . '/src/api/scripts/l10n/en/timeline.js');
  drupal_add_js(libraries_get_path('standalone-timeline') . '/src/api/scripts/l10n/en/labellers.js');

  drupal_add_js(drupal_get_path('module', 'islandora_gis_timeline') . '/js/build/islandora_gis_timeline.js');

}
