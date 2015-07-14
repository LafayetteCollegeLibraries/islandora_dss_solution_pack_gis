<?php

/**
 * @file
 * Integration with Islandora
 * Processing functions for layers and behaviors
 * @author griffinj@lafayette.edu
 *
 */

/**
 * Initialize the layer array into an indexed array of layer objects
 *
 * @param $layers
 *   Array of layers to process
 * @param $map
 *   Map array
 * @return $layer_data
 *   Array of initialized layer objects
 */
function _islandora_gis_layers_process($layers = array(), $map = array()) {
  $layer_data = array();

  // Load Layers and assign weights
  foreach ($layers as $key => $layer) {
    
    // Ensure that Islandora Object Datastream Layers are only generated using openlayers_get_layer_object()
    if ($layer_object = openlayers_get_layer_object($layer, $map)) {
      $layers[$key] = $layer_object;
      if (!empty($map['layer_weight'][$key])) {
        $layers[$key]->weight = $map['layer_weight'][$key];
      }
      else $layers[$key]->weight = 0;
    }
    else unset($layers[$key]);
  }

  // Sort layers
  usort($layers, '_openlayers_layers_process_sort');

  // Process into array-based layer data for the map
  foreach ($layers as $type => $layer_object) {
    if (is_object($layer_object)) {
      $layer_object->render($map);
      $layer_object->data['title'] = $layer_object->title;
      $layer_object->data['weight'] = $layer_object->weight;
      $layer_data[$layer_object->name] = $layer_object->data;
    }
  }

  return $layer_object->data;
}
