# Libraries
## ShapefileProcessor
Following the design patterns within earlier Islandora Solution Packs, the _ShapefileProcessor_ provides the interface necessary to utilize [ogr2ogr](http://www.gdal.org/ogr2ogr.html) in order to generate the following derivative files from ESRI Shapefile contents:

* GML Documents
* KML Documents
* GeoJSON Objects

### GeoJSON Validation
ShapefileProcessor also ensures that valid GeoJSON is generated from the ogr2ogr binary by validating against the JSON Schema Object at [https://github.com/fge/sample-json-schemas](https://github.com/fge/sample-json-schemas).

### Forthcoming Features
* TopoJSON Object derivation
