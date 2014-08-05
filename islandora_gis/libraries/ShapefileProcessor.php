<?php

  /**
   * @file The ShapefileProcessor class (for the generation of Shapefile derivatives)
   *
   * @author griffinj@lafayette.edu
   */

require 'vendor/autoload.php';
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

    // Get the schema and data as objects
    $this->json_schema_retriever = new JsonSchema\Uri\UriRetriever;
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

    return $doc->schemaValidate($doc_schema_uri);
  }

  /**
   * Validate the JSON Object against a schema
   *
   * @param string $json_file_path The file path to the JSON Object being validated
   * @param string $uri The URL for the JSON Schema Object
   * @return string A concatenated string of validation errors
   * @access private
   *
   */
  private function validateJson($json_file_path, $uri = 'file://schema.json') {

    $schema = $this->json_schema_retriever->retrieve($uri);
    $data = json_decode($json_file_path);

    // If you use $ref or if you are unsure, resolve those references here
    // This modifies the $schema object

    /**
     * This fails for the current schema implementation
     * @todo Evaluate GeoJSON schemata Objects which can have URI references resolved
     *
     */
    //$refResolver = new JsonSchema\RefResolver($this->json_schema_retriever);
    //$refResolver->resolve($schema, 'file://' . __DIR__);

    // Validate against the JSON Schema
    $validator = new JsonSchema\Validator();
    $validator->check($data, $schema);
    if ($validator->isValid()) {

      return TRUE;
    }

    // ..and if there are errors, return them within a concatenated string.
    $errors = array_map(function($error) {

	return sprintf("[%s] %s\n", $error['property'], $error['message']);
      }, $validator->getErrors());

    return "JSON does not validate. Violations:\n" . implode("\n", $errors);
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
   * Generate the GML Document derivative
   *
   * @param type $parameterArray
   * @param type $dsid
   * @param type $file
   * @param type $file_ext
   * @return The resulting value of the ogr2ogr invocation
   */
  public function deriveGml($parameterArray = NULL, $dsid, $shape_file_path, $file_ext) {

    // Construct the file path for the KML Document
    $gml_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    // Invoke the ogr2ogr binary in order to generate the KML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f GML', $gml_file_path, $shape_file_path);

    // Validate against the schema
    //$this->validate($gml_file_path, self::GML_SCHEMA_URI);

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
  public function deriveKml($parameterArray = NULL, $dsid, $shape_file_path, $file_ext) {

    // Construct the file path for the KML Document
    $kml_file_path = preg_replace('/\.shp$/', ".$file_ext", $shape_file_path);

    // Invoke the ogr2ogr binary in order to generate the KML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f KML', $kml_file_path, $shape_file_path);

    // Validate against the schema
    //$this->validate($kml_file_path, self::KML_SCHEMA_URI);      

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

    $this->validateJson($json_file_path, 'file://' . __DIR__ . '/json/geojson_schema.json');

    if ($returnValue == '0') {

      // Ingest the GeoJSON file into the "JSON" datastream
      $_SESSION['fedora_ingest_files'][$dsid] = $json_file_path;
      return TRUE;
    } else {

      return $returnValue;
    }
  }

  }
