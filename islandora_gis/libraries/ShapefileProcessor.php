<?php

  /**
   * @file The ShapefileProcessor class (for the generation of Shapefile derivatives)
   *
   * @author griffinj@lafayette.edu
   */

include 'vendor/autoload.php';

/**
 * ShapefileProcess Class implements the methods defined in the Islandora GIS Shapefile Content Model
 *
 */
class ShapefileProcessor {

  //const GML_SCHEMA_URI = 'http://schemas.opengis.net/gml/3.2.1/gmlBase.xsd';
  const GML_SCHEMA_URI = 'http://schemas.opengis.net/gml/3.2.1/gml.xsd';
  const KML_SCHEMA_URI = 'https://developers.google.com/kml/schema/kml21.xsd';

  private $ogr2ogr_bin_path;

  // Overriding this in order to resolve issues with mapshaper
  //protected $simplify = '10%';

  protected $simplify_method = 'visvalingam';

  const DOUGLAS_PEUCKER = 'rdp';
  const VISVALINGAM = 'visvalingam';

  //protected $quantization = TRUE;

  // For topojson
  protected $simplify = '7e-10';
  //protected $quantization = '1e5';
  protected $quantization = FALSE;

  // Please see http://php.net/manual/en/function.rmdir.php#114183
  public static function delTree($dir) {

    $files = array_diff(scandir($dir), array('.','..'));

    foreach ($files as $file) {

      (is_dir("$dir/$file") && !is_link($dir)) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  } 

  /**
   * Constructor
   * @param string $ogr2ogr_bin_path The path to the ogr2ogr binary
   * @param string $topojson_bin_path The path to the topojson command-line interface
   *
   */
  public function __construct($ogr2ogr_bin_path = '/usr/bin/env ogr2ogr',
			      $topojson_bin_path = 'js/node_modules/topojson/bin/topojson',
			      $geojson_bin_path = 'js/node_modules/topojson/bin/topojson-geojson',
			      $mapshaper_bin_path = 'js/node_modules/mapshaper/bin/mapshaper',
			      $mapshaper_arguments = array()) {

    $this->ogr2ogr_bin_path = $ogr2ogr_bin_path;
    $this->topojson_bin_path = $topojson_bin_path;
    $this->geojson_bin_path = $geojson_bin_path;
    $this->mapshaper_bin_path = $mapshaper_bin_path;

    // Set the arguments for mapshaper
    foreach($mapshaper_arguments as $key => $value) {
      
      if(object_has_property($this, $key)) {

	$this->$key = $value;
      }
    }

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
  protected function validateXml($doc_file_path, $doc_schema_uri) {

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
  protected function validateJson($json_file_path, $uri = 'file://schema.json') {

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
  protected function ogr2ogr() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->ogr2ogr_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Invoke the topojson command-line interface within the local environment
   *
   * @return string The resulting value of the command-line invocation
   * @access private
   *
   */
  protected function topojson() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->topojson_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Invoke the geojson command-line interface within the local environment
   *
   * @return string The resulting value of the command-line invocation
   * @access private
   *
   */
  protected function geojson() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->geojson_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Invoke the mapshaper command-line interface within the local environment
   *
   * @return string The resulting value of the command-line invocation
   * @access private
   *
   */
  protected function mapshaper() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->mapshaper_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Method for simplifying derivatives
   * @param string $dsid The ID of the Datastream managing the derivative
   * @todo Fully implement
   *
   */
  protected function simplify($dsid) {

    $returnValue = FALSE;

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

/**
 * ShapefileObjectProcessor Class
 * Provides the derivative-generation functionality for Esri Shapefiles
 *
 */
class ShapefileObjectProcessor extends ShapefileProcessor {

  private $object;
  private $shape_file_path;
  private $gml_file_path;
  private $kml_file_path;
  private $json_file_path;

  /**
   * Constructor
   *
   */
  public function __construct($object, $shape_file_path = NULL,
			      $ogr2ogr_bin_path = '/usr/bin/env ogr2ogr',
			      $topojson_bin_path = 'js/node_modules/topojson/bin/topojson',
			      $geojson_bin_path = 'js/node_modules/topojson/bin/topojson-geojson',
			      $mapshaper_bin_path = 'js/node_modules/mapshaper/bin/mapshaper',
			      $mapshaper_arguments = array()) {

    $this->object = $object;    
    $this->shape_file_path = isset($shape_file_path) ? $shape_file_path : $this->getShapefile($object);
    $this->shp_file_path = $this->getShape();

    parent::__construct($ogr2ogr_bin_path, $topojson_bin_path, $geojson_bin_path, $mapshaper_bin_path);
  }

  /**
   * Destructor
   *
   */
  public function __destruct() {

    $this->deleteShapefile($this->shp_file_path, $this->object);
  }

  /**
   * Retrieve the .shp file from the compressed Shapefile
   * @param string $shape_file_path the file system path to the compressed Shapefile
   * return string the file path to the .shp file
   */
  private function getShape($shape_file_path = NULL) {

    $dir_path = '/tmp/' . preg_replace('/[\s:]/', '_', $this->object->id);
    $file_path = $dir_path . '_SHP.zip';
    $shapefile_content_path = $dir_path . '_SHP';

    $shape_file_path = $shape_file_path ?: $this->shape_file_path;

    $zip = new ZipArchive;

    // Create the directory if it does not exist
    if(!file_exists($shapefile_content_path)) {

      mkdir($shapefile_content_path);
    }
    if($zip->open($shape_file_path) === TRUE) {

      $zip->extractTo($shapefile_content_path);
      $zip->close();
    } else {

      throw new Exception("Failed to decompress $shape_file_path to the path $shapefile_content_path");
    }

    $files = glob($shapefile_content_path . '/*.[Ss][Hh][Pp]');
    if(!empty($files)) {

      $shp_file_path = array_shift($files);
    } else {

      throw new Exception("Failed to parse the .shp/.SHP file from $shape_file_path");
    }

    return $shp_file_path;
  }
  
  /**
   * Decompress and retrieve the path to the SHP file
   * @param FedoraObject $object
   * @returns string the file system path to the SHP file
   *
   */
  private function getShapefile($object) {

    // Retrieve the compressed Shapefile from Islandora
    $dir_path = '/tmp/' . preg_replace('/[\s:]/', '_', $object->id);
    $file_path = $dir_path . '_SHP.zip';
    $file = fopen($file_path, 'wb');

    fwrite($file, $object['SHP']->content);
    fclose($file);

    return $file_path;
  }

  /**
   * Delete the decompressed Shapefile content
   * @param string the file system path to the SHP file
   *
   */
  private function deleteShapefile($shp_file_path, $object) {

    $shapefile_content_dir = dirname($shp_file_path);

    // Remove the directory itself
    if(file_exists($shapefile_content_dir)) {

      // Remove the contents of the directory
      $files = array_diff(scandir($shapefile_content_dir), array('.','..'));

      foreach($files as $file) {

	if(is_dir("$shapefile_content_dir/$file")) {

	  self::delTree("$shapefile_content_dir/$file");
	} else {

	  unlink("$shapefile_content_dir/$file");
	}
      }

      rmdir($shapefile_content_dir);
    }
  }

  /**
   * Method for simplifying derivatives
   * @param string $dsid The ID of the Datastream managing the derivative
   *
   */
  public function simplify($force_geo_json = TRUE, $precision = NULL) {

    $returnValue = FALSE;

    /*
    foreach(array('gml', 'kml', 'json') as $derivative_type) {

      $prop_name = "$derivative_type_file_path";
      if(filesize($this->$prop_name) > 1024*520) {

	
      }
    }
    */

    print $this->object['JSON']->size . "\n";
    print filesize($this->json_file_path) . "\n";

    if($force_geo_json or filesize($this->json_file_path) > 1024*1024) {

      $topo_json_file_path = $this->json_file_path;

      // Invoke the interface for reducing TopoJSON into GeoJSON
      if(isset($precision)) {

	print "trace1\n";
	$this->geojson("--precision $precision", $this->json_file_path);
      } else {

	print "trace2\n";
	$this->geojson($this->json_file_path);
      }

      $shp_file_base = basename($this->shp_file_path, '.shp');

      // Retrieve the JSON Objects generated by TopoJSON
      $files = array_filter(glob("$shp_file_base.json"), function($e) {
	  
	  return preg_match('/[^\.]+\.json/', $e);
	});

      if(!empty($files)) {

	$this->json_file_path = array_shift($files);
      } else {

	throw new Exception("Failed to parse the GeoJSON file transformed from the TopoJSON");
      }

      if(!$force_geo_json) {

	// Ingest the origin TopoJSON Object into a new "TopoJSON" Datastream
	$topo_json_ds = $object->constructDatastream('TOPOJSON', 'X');
	$topo_json_ds->label = 'TopoJSON Object';
	$topo_json_ds->mimetype = 'application/vnd.geo+json';
	$topo_json_ds->setContentFromFile($topo_json_file_path);

	try {

	  $object->ingestDatastream($topo_json_ds);
	} catch (Exception $e) {
      
	  watchdog('islandora_gis', $e->getMessage(), NULL, WATCHDOG_ERROR);
	  drupal_set_message(t('A problem occured while ingesting the datastream "@label" for @pid, please notify the administrator.', array('@label' => $topo_json_ds->label, '@pid' => $object->id)), 'error');
	}
      }

      // Re-ingest the GeoJSON Object into the "JSON" Datastream
      $json_ds = $this->object['JSON'];
      $json_ds->setContentFromFile($this->json_file_path);

      // Convert the GeoJSON Object into the KML
      $kml_file_path = $this->kml_file_path;
      $returnValue = $this->ogr2ogr('-f KML', $kml_file_path, $this->json_file_path);

      // Re-ingest the KML Document into the "KML" Datastream
      $kml_ds = $this->object['KML'];
      $kml_ds->setContentFromFile($this->kml_file_path);

      $returnValue = $this->json_file_path;
    }

    return $returnValue;
  }

  /**
   * Generate the GML Document for ingestion
   * @return string the file system path to the GML Document
   *
   */
  public function deriveGml() {

    $shp_file_path = $this->shp_file_path;

    // Construct the file path for the GML Document
    $gml_file_path = preg_replace('/\.shp$/', ".gml.xml", $shp_file_path);

    // @todo Refactor
    $this->gml_file_path = $gml_file_path;

    // Invoke the ogr2ogr binary in order to generate the GML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f GML', $gml_file_path, $shp_file_path);

    // Validate against the schema
    /**
     * Fails, please see http://gis.stackexchange.com/questions/6721/why-doesnt-the-gml-3-1-1-schema-validate-with-xmllint
     * @todo Refactor with another validation service
     */
    //$this->validateXml($gml_file_path, self::GML_SCHEMA_URI);      

    if ($returnValue == '') {

      return $gml_file_path;
    } else {

      return $returnValue;
    }
  }

  /**
   * Normalizes the KML Document for validation
   * @todo Investigate why this is not addressed using ogr2ogr
   *
   */
  private function normalizeKml($kml_file_path) {

    /**
     * @todo Implement for the failed KML validation
     *
     */

    return FALSE;
  }

  /**
   * Generate the KML Document for ingestion
   * @return string the file system path to the KML Document
   *
   */
  public function deriveKml() {

    $shp_file_path = $this->shp_file_path;

    // Construct the file path for the KML Document
    $kml_file_path = preg_replace('/\.shp$/', ".kml.xml", $shp_file_path);

    // @todo Refactor
    $this->kml_file_path = $kml_file_path;

    // Invoke the ogr2ogr binary in order to generate the KML Document from the .SHP file
    $returnValue = $this->ogr2ogr('-f KML', $kml_file_path, $shp_file_path);

    // Validate against the schema
    /**
     * Fails, ogr2ogr generates a KML Documents with the root element out of namespace:
     * <kml><Document><Folder>...
     * (should be)
     * <kml:kml><Document><Folder>...
     * @todo Integrate with ShapefileProcessor::normalizeKml()
     */
    //$this->validateXml($kml_file_path, self::KML_SCHEMA_URI);      

    if ($returnValue == '') {

      return $kml_file_path;
    } else {

      return $returnValue;
    }
  }

  /*
   * Generate a TopoJSON Object from the .shp File
   *
   * @return string the TopoJSON Object
   */
  public function deriveTopoJson() {

    $shp_file_path = $this->shp_file_path;

    // Construct the file path for the GeoJSON Object
    $json_file_path = preg_replace('/\.shp$/', ".geojson.json", $shp_file_path);

    // Determine whether to generate the TopoJSON using topojson or mapshaper
    if(isset($this->mapshaper_bin_path) and file_exists($this->mapshaper_bin_path)) {

      $mapshaper_args = array($shp_file_path, "-o $json_file_path", 'format=topojson');

      // For handling simplification...
      if($this->simplify) {

	$simplify_args = array();
	$simplify_args[] = "-simplify";

	if($this->simplify_method != self::VISVALINGAM) {
	// @todo Restructure
	//if($this->simplify_method != 'visvalingam') {

	  $simplify_args[] = $this->simplify_method;
	}

	$simplify_args[] = $this->simplify;
	$mapshaper_args[] = implode(' ' , $simplify_args);
      }
      
      // For handling quantization...
      if($this->quantization) {

	if(gettype($this->quantization) == 'int') {

	  $mapshaper_args[] = "quantization={$this->quantization}";
	}
      } else {

	$mapshaper_args[] = "no-quantization";
      }

      //$returnValue = $this->mapshaper(implode(' ', $mapshaper_args));
      $returnValue = call_user_func_array(array($this, 'mapshaper'), $mapshaper_args);
    } else {

      $topojson_args = array("-o $json_file_path", $shp_file_path);

      // @todo Extend
      if($this->simplify) {

	$topojson_args[] = "-s {$this->simplify}";
      }

      if($this->quantization) {

	$topojson_args[] = "-q {$this->quantization}";
      }

      //$returnValue = $this->topojson("-o $json_file_path", $shp_file_path);
      $returnValue = call_user_func_array(array($this, 'topojson'), $topojson_args);

      // @todo Refactor
      $returnValue = $json_file_path;

      /**
       * This merely generates the TopoJSON for future integration with OpenLayers 3
       * Unfortunately, this is also the process through which simplification and/or quantization must be undertaken
       */
    }

    return $returnValue;
  }

  /**
   * Generate a GeoJSON Object from the .shp File
   *
   * @return string The cURL session results from the POST request to ogre
   */
  public function deriveJson($force_geo_json = TRUE) {

    $shp_file_path = $this->shp_file_path;

    // Construct the file path for the GeoJSON Object
    $json_file_path = preg_replace('/\.shp$/', ".geojson.json", $shp_file_path);

    // @todo Refactor
    $this->json_file_path = $json_file_path;

    // Attempt to generate the derivative as a TopoJSON Object
    //if(!$force_geo_json and isset($this->topojson_bin_path) and file_exists($this->topojson_bin_path)) {
    if(isset($this->topojson_bin_path) and file_exists($this->topojson_bin_path)) {

      $returnValue = $this->deriveTopoJson();
    } else {

      // Submit the POST request to ogre
      //$returnValue = $this->post($this->ogreUri . '/convert', array('file_contents' => '@' . $shp_file_path), $json_file_path);
      /**
       * ogr2ogr seems to require that vector data sets be reprojected into the EPSG:4326 SRS
       * Resolves GEO-25
       */
      $returnValue = $this->ogr2ogr('-t_srs EPSG:4326', '-f GeoJSON', $json_file_path, $shp_file_path);
    }

    // Validate the JSON output
    $this->validateJson($json_file_path, 'file://' . __DIR__ . '/json/geojson_schema.json');

    // Simplify the other derivatives using TopoJson
    // @todo Refactor
    //if(!$force_geo_json and isset($this->topojson_bin_path) and file_exists($this->topojson_bin_path)) {
    //$returnValue = $this->simplify($force_geo_json);

    if ($returnValue == '') {

      return $this->json_file_path;
    } else {

      return $returnValue;
    }
  }

  /**
   * Wrapper method for the invocation of the derivation methods using a Datastream ID
   * @param string $dsid
   *
   */
  public function derive($dsid) {

    $method_name = 'derive' . substr($dsid, 0, 1) . substr($dsid, 1);

    if(method_exists($this, $method_name)) {
      
      return call_user_func_array(array($this, $method_name), array());
    }
  }
}
