<?php

require dirname(__DIR__) . '/ShapefileProcessor.php';

/**
 * Test for the ShapefileProcessor
 * @author griffinj@lafayette.edu
 *
 */
class ShapefileProcessorTest extends PHPUnit_Framework_TestCase {

  protected $processor;

  protected function setUp() {

    $this->processor = new ShapefileProcessor();
    $this->source_file = dirname(__DIR__) . '/tests/fixtures/test.shp';
    $this->geo_json_file = dirname(__DIR__) . '/tests/fixtures/test.geojson.json';
  }

  //   public function deriveGml($parameterArray = NULL, $dsid, $file, $file_ext) {
  public function testDeriveGml() {

    
  }

  //  public function deriveKml($parameterArray = NULL, $dsid, $file, $file_ext) {
  public function testDeriveKml() {

  }

  //deriveJson($parameterArray = NULL, $dsid, $shape_file_path, $file_ext)
  public function testDeriveJson() {

    $this->processor->deriveJson(array(), 'JSON', $this->source_file, '.geojson.json');
  }

}

?>
