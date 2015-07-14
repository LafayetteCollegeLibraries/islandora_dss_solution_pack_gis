Islandora GIS Solution Pack
===============================

# Islandora Content Model and Modules for the Management of GIS Material

## Functional Requirements

### Lafayette College Libraries
_(Much of this was identified by John H. Clark, the Data Visualization and GIS Librarian at Lafayette College)_

* Managing Base Maps (for interfacing with a Web Mapping Service [e. g. GeoServer])
 * Managing TIFF images as datastream content within the repository
 * Managing GeoTIFF (georectified) images within the WMS (GeoServer)
     * *Should a persistent URI to this be stored somewhere within a RELS-EXT triple to this asset?*
 * Managing a World file generated from the GeoTIFF (text/plain)

* Managing Feature Sets (for interfacing with a Web Feature Service [GeoServer])
 * Managing an ESRI Shapefile
  * The zipped contents of the Shapefile should be managed within a datastream (application/zip)
  * *Should a separate Object also be managed (and linked via RELS-EXT) which could contain the components of a Shapefile, or would this be too complex?*
     * *In the case that a linked, Shapefile Object is desirable...*
     * DBF content should be managed within a datastream (application/dbase) *(Please see [the following](http://www.digitalpreservation.gov/formats/fdd/fdd000325.shtml))*
	 * SHP content should be managed within a datastream (application/x-qgis) *(A more preferable MIME type could not be located)*
	 * SHX content should be managed within a datastream
	 * PRJ content should be managed within a datastream
  * KML data should be managed within a datatream (application/vnd.google-earth.kml+xml) **(inline XML)**
  * GML data may be managed within a datastream (application/gml+xml) **(inline XML)**
     * *For our cases this could, potentially, be derived from the GML Document*
  * TopoJSON data may be managed within a datastream (application/json) *(we intend to use this for JavaScript visualization)*
  * MODS data should be managed within a datastream **(inline XML)** (this would follow the schema outlined by contributors to the GeoHydra Project [i. e. GML envelopes and other OGP vocab. terms integrated with a MODS schema])
     * *We were intending to request to use [an XSL Stylesheet developed by the GeoHydra contributors](https://github.com/sul-dlss/geohydra/blob/master/ogp/fgdc2mods.xsl)*

## University of Toronto Scarborough Campus
_(This was posed as a question by [Kim Pham](https://twitter.com/tolloid) of the Digital Scholarship Unit at the UTSC Library)_

* Managing Feature Sets (for interfacing with a Web Feature Service [GeoServer])
 * Managing an ESRI Shapefile
     * How must .shp.xml content be managed?
     * How must .mxd content be managed?
     * How must .lyr content be managed?

## User Stories

### Lafayette College Libraries
*(Forthcoming)*
