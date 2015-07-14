# Islandora GIS Crowdsourcing
_Capturing geospatial metadata (i. e. geotagging) from Users_

## [Cartaro](https://www.drupal.org/project/cartaro) and Managing Geospatial Data

This Module is to serve as a means by which to integrate the [OpenLayers Editor](https://www.drupal.org/project/ole) from the Cartaro project into an Islandora Site.

* Still being discussed is how best to structure a workflow in relation to the persistence of "geotagging" Features in relation to Islandora GeoTIFF Objects
  * Outlining an example...
      * Users create geospatial Features as Drupal Content Nodes using Islandora GeoTIFF Object(s) as raster map layers
      * Geospatial Features must then be packaged for ingestion into Islandora as a persistent Shapefile Object
      * _Which roles possess permissions for this ingestion?_
      * _How is geospatial metadata captured within this process?_
