# Islandora GIS
_Managing Esri Shapefiles and GeoTIFF's within the Islandora Ecosystem_

## The Shapefile Content Model
Please see the following resources for documents in relation to data modeling for Shapefiles:

* [Logical Data Models](https://www.lucidchart.com/documents/view/bd3f98c5-7b6e-4f30-82cc-42e159c8d5b5)

## Functionality for Shapefiles

### Ingestion Functionality

* Derivative Generation
  * ogr2ogr ([GDAL](http://www.gdal.org/)) is used to derive KML and GML Documents from Esri Shapefiles
  * topojson ([topojson](https://github.com/mbostock/topojson)) is used to derive GeoJSON Objects from Esri Shapefiles
      * This requires a local installation of [Node.js](https://nodejs.org/)
  * Should there be no WMS or WFS service available, OpenLayers can render the content of the datastreams for these derivatives
* Geospatial Metadata
      * FGDC Documents are transformed into MODS-OGP Documents (using an XSL Stylesheet from [the GeoHydra MetaData ToolKit](https://github.com/sul-dlss/geohydra))

### Linking Layer Features (e. g. points or polygons) to Islandora Objects (e. g. Large Image Objects)
* Using the <dc:relation /> Element within the DC Datastream, a Feature ID (FID) is specified in relation to a WFS endpoint
  * This permits one to link points on any given map with an Islandora Object
  * By structuring these relationships for the Drupal 7 JavaScript API, one can then resolve links between map layer Features with Objects
      * This is used at the Lafayette College Libraries to offer "pop-up" widgets rendering Islandora Object thumbnails and metadata fields
* Limitations
  * Currently, only SPARQL queries resolve the relationships within the DC Datastream
      * Further, persistent URL's are not minted from the WFS endpoint; Hence, this is *not* properly structured linked data
  * This only been implemented with Large Image Objects within the scope

## Functionality for GeoTIFF's

* The [ExifTool](http://www.sno.phy.queensu.ca/~phil/exiftool/) is used to determine whether or not a TIFF has been georeferenced
* gdalinfo ([GDAL](http://www.gdal.org/)) is used to extract the bounding box for GeoTIFF's

## Geospatial Metadata Management

Currently, the following geospatial metadata formats are supported within the solution pack:

* FGDC Metadata [Content Standard for Digital Geospatial Metadata](https://www.fgdc.gov/standards/projects/FGDC-standards-projects/metadata/base-metadata/index_html)

Further, a prototypal [MODS-XML Form](xml/islandora_shapefile_mods_form.xml) has been structured for the capturing of metadata from users.  Scoped for future releases are the following geospatial metadata formats:

* ISO 19139 ([the XML implementation of 19115](http://www.iso.org/iso/iso_catalogue/catalogue_tc/catalogue_detail.htm?csnumber=32557))

_Please note that NOAA offers an [XSL Stylesheet](http://www.ncddc.noaa.gov/metadata-standards/metadata-xml/) which transforms FGDC Documents into the ISO 19139_

## Drush integration

Drush hooks have been implemented as the primary means through which to manage Shapefiles and GeoTIFF's:

* Vector data sets (Esri Shapefiles)
* GeoTIFF's

Please note that these are managed within the Fedora Commons repository.  Extended integration with a Web Map Service (WMS) or Web Feature Service (WFS) is offered by the islandora\_gis\_geoserver Module.
