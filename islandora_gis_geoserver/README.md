# Islandora GIS for GeoServer

## A library for interfacing with [GeoServer](http://geoserver.org/)

* This library was implemented using the [Guzzle framework](http://guzzle.readthedocs.org/en/latest/)
  * Due to certain conflicts within the dependencies for Guzzle releases beyond those of 3.x and PHP components of the Drupal 7.x environment, this RESTful consumer must remain on Guzzle releases 3.x

## Managing Shapefiles and GeoTIFF's

### Specifying base maps
* The predicate _fedora-rels-ext:hasDependent_ is used to relate Shapefile Objects to Islandora Large Image Objects (storing GeoTIFF's)
* This provides a means by which to render one or many base maps in relation to Shapefile Objects

## Integration with OpenLayers
_Please note here that Islandora Object Datastream content is replicated between Fedora Commons and GeoServer._

* WMS layers are structured for OpenLayers using the _openlayers\_layer\_type\_wms_ plug-in offered by the [geoserver Drupal Module](https://www.drupal.org/project/geoserver)
* Similarly, WFS layers are structured for OpenLayers using the _openlayers\_layer\_type\_geoserver\_wfs_ plug-in
  * In both cases, the URL's for the layers are constructed using the following elements:
    1. The base URL of the GeoServer instance
    2. The name of the GeoServer Workspace in which the Islandora resources are managed (this may be empty if the default Workspace is used)
    3. The name of the resource (either a Coverage or Feature Type) generated from the Islandora Object PID (':' characters are replaced with '_')

### Rendering Islandora Collection Objects
* In addition to Islandora Shapefile Objects being individually rendered using OpenLayers, Islandora Collection Objects can also be rendered
  * For these cases, multiple Feature Type (as well as all related Base Maps) are rendered

## Issues and Challenges
* Generating the bounding boxes for GeoTIFF's (by utilizing values stored within the <dc:coverage> Element)
* Generating the initial zoom depth and bounding boxes for Shapefiles (by utilizing values stored within the <dc:coverage> Element)
* Abstracting and managing WMS and WFS layer titles
  * Currently, an additional mapping is required for at least one project undertaken by the Lafayette College Libraries
