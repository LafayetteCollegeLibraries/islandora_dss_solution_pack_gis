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
  }
