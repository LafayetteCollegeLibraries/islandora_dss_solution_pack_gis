<?php

  /**
   * @file The ShapefileProcessor class (for the generation of Shapefile derivatives)
   *
   * @author griffinj@lafayette.edu
   */

require 'Ogre.php';

/**
 * ShapefileProcess Class implements the methods defined in the Islandora GIS Shapefile Content Model
 *
 */
class ShapefileProcessor {

  const GML_SCHEMA_URI = 'http://schemas.opengis.net/gml/3.2.1/gmlBase.xsd';
  const KML_SCHEMA_URI = 'https://developers.google.com/kml/schema/kml21.xsd';

  private $ogre;
  private $ogr2ogr_bin_path;

  /**
   * Constructor
   * @param string $ogreUri The URI for the ogre Node.js app.
   * @param string $ogr2ogr_bin_path The path to the ogr2ogr binary
   *
   */
  function ShapefileProcessor($ogreUri = 'http://localhost:3000', $ogr2ogr_bin_path = '/usr/bin/env ogr2ogr') {

    //$this->ogre = new Ogre($ogreUri);
    $this->ogr2ogr_bin_path = $ogr2ogr_bin_path;
  }

  private function validateXml($schemaDoc) {

    return FALSE;
  }

  /**
   * Validate the JSON Object against a schema
   *
   */
  private function validateJson() {

    return FALSE;
  }

  /**
   * Invoke the ogr2ogr binary within the local environment
   *
   * @return string The resulting value of the command-line invocation
   * @access private
   *
   */
  private function ogr2ogr() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->ogr2ogr_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Validate the XML Document against a schema
   *
   * @param string $doc_file_path The file path to the XML Document being validated
   * @param string $doc_schema_uri The URI for the XML Schema Document
   * @return string The results of the validation
   * @access private
   *
   */
  private function validate($doc_file_path, $doc_schema_uri) {

    $doc = new DOMDocument();
    $doc->load($doc_file_path);

    return $doc->schemaValidate('schema.xsd');
  }

  /**
   * Generate the GML Document derivative
   *
   * @param type $parameterArray
   * @param type $dsid
   * @param type $file
   * @param type $file_ext
   * @return The resulting value of the ogr2ogr invocation
   */
  public function deriveGml($parameterArray = NULL, $dsid, $file, $file_ext) {

    // Construct the file path for the KML Document
    $gml_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    // Invoke the ogr2ogr binary in order to generate the KML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f GML', $gml_file_path, $shape_file_path);

    // Validate against the schema
    $this->validate($shape_file_path, self::GML_SCHEMA_URI);

    if ($returnValue == '0') {

      // Ingest the GML Document into the "GML" datastream
      $_SESSION['fedora_ingest_files'][$dsid] = $gml_file_path;
      return TRUE;
    } else {

      return $returnValue;
    }
  }

  /**
   * Generate the KML Document derivative
   *
   * @param type $parameterArray
   * @param type $dsid
   * @param type $file
   * @param type $file_ext
   * @return The resulting value of the ogr2ogr invocation
   */
  public function deriveKml($parameterArray = NULL, $dsid, $file, $file_ext) {

    // Construct the file path for the KML Document
    $kml_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    // Invoke the ogr2ogr binary in order to generate the KML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f KML', $kml_file_path, $shape_file_path);

    // Validate against the schema
    $this->validate($shape_file_path, self::KML_SCHEMA_URI);      

    if ($returnValue == '0') {

      // Ingest the KML Document into the "KML" datastream
      $_SESSION['fedora_ingest_files'][$dsid] = $kml_file_path;
      return TRUE;
    } else {

      return $returnValue;
    }
  }

  /**
   * Generate a TopoJSON Object from an ArcInfo Geodatabase file
   * @param type $parameterArray
   * @param type $dsid
   * @param type $file
   * @param type $file_ext
   * @todo Implement with an esri2open-based service
   * @return The resulting value of the ogr2ogr invocation
   */
  public function deriveTopoJson($parameterArray = NULL, $dsid, $shape_file_path, $file_ext) {

    // Construct the file path for the GeoJSON Object
    $json_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    $returnValue = FALSE;

    // Submit the POST request to the TopoJSON generating service
    // @todo Integrate with the Python TopoJSON service
    
    return $returnValue;
  }

  /**
   * Generate a GeoJSON Object from the .shp File
   *
   * @param type $parameterArray
   * @param type $dsid
   * @param type $file
   * @param type $file_ext
   * @return string The cURL session results from the POST request to ogre
   */
  public function deriveJson($parameterArray = NULL, $dsid, $shape_file_path, $file_ext) {

    // Construct the file path for the GeoJSON Object
    $json_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    // Submit the POST request to ogre
    //$returnValue = $this->post($this->ogreUri . '/convert', array('file_contents' => '@' . $shape_file_path), $json_file_path);
    $returnValue = $this->ogr2ogr('-f GeoJSON', $json_file_path, $shape_file_path);
    
    if ($returnValue == '0') {

      // Ingest the GeoJSON file into the "JSON" datastream
      $_SESSION['fedora_ingest_files'][$dsid] = $json_file_path;
      return TRUE;
    } else {

      return $returnValue;
    }
  }

  }
