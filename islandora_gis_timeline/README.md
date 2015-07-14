# Islandora GIS Timeline
_Exposing Events in Relation to Maps Using [SIMILE Timeline](http://www.simile-widgets.org/timeline/)_

## The SIMILE Timeline Widget

This Module integrates the [SIMILE Timeline widget fork](https://github.com/Lab21k/standalone-timeline) maintained by [Lab21k](https://github.com/Lab21k)

### Timeline Events

Timeline Events are to be structured using JSON Objects related to Islandora Shapefile Objects

* Currently, the anticipated approach is to utilize an _EVENTS_ Datastream
* One alternative would be to structure events using an ontology in the RDF (e. g. the [LODE](http://linkedevents.org/ontology/)), and to manage these within the _RELS-EXT_ Datastream
  * However, this would introduce performance costs, and ultimately still require that a JSON Object be generated
* Using a JavaScript event handler, one can ensure that clicking upon any given Timeline Event will trigger a behavior within the OpenLayers Map instance
  * This requires that a singleton Map Object be bound to the DOM using the OpenLayers JavaScript library
  * The JavaScript functionality developed within the [openlayers Drupal Module](https://www.drupal.org/project/openlayers) provides precisely this.

### Temporal Navigation
* Individual WMS and WFS layers are activated or deactivated based upon a temporal range specified using a "slider" widget
  * Currently, this is implemented using the [jQuery UI Slider](https://jqueryui.com/slider/)
  * The dates themselves are parsed from the Islandora Shapefiles (using the _dc:coverage_ metadata element) containing the Features being rendered
  * Ideally, this could also be integrated with the WMS-T functionality offered by applications such as GeoServer
    * While [OpenLayers does support such a feature](http://dev.openlayers.org/examples/wmst.html), GeoServer (and, presumably, other Web Map Services) require an administrator to manually set the Shapefile attribute to be used as a temporal dimension

### Transitional Animations
* The anticipated approach for integrating animations between the Events is to utilize the OpenLayers API ([ol.animation](http://openlayers.org/en/v3.7.0/apidoc/ol.animation.html)) for animation
  * A straightforward integration of this functionality can be found within the [OpenLayers examples](http://openlayers.org/en/v3.7.0/examples/animation.html)
