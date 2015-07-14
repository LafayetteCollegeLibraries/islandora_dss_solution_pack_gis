<?php

  /**
   * @file Theming hook implementations and functionality
   * @author griffinj@lafayette.edu
   *
   */

/**
 * Implements hook_preprocess().
 *
 * @param array $variables
 *   an array of variables that will be passed to the theme function
 */
function islandora_gis_geoserver_preprocess_islandora_gis(&$vars) {

  $islandora_object = $vars['islandora_object'];

  dpm($islandora_object['DC']->content);

  }

/**
 * Implements template_preprocess_HOOK().
 */
function islandora_gis_geoserver_preprocess_islandora_basic_collection_wrapper(&$vars) {

  // Populate the Islandora Feature Map array

  variable_set('islandora_gis_geoserver_islandora_feature_map', array('lebaneseTown_2.1' => '<div>trace</div>'));
}


