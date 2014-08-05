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
    $this->gml_file = dirname(__DIR__) . '/tests/fixtures/test.gml.xml';
    $this->kml_file = dirname(__DIR__) . '/tests/fixtures/test.kml.xml';
    $this->geo_json_file = dirname(__DIR__) . '/tests/fixtures/test.geojson.json';
  }

  /**
   *
   */
  public function testDeriveGml() {

    $this->processor->deriveGml(array(), 'GML', $this->source_file, 'gml.xml');
  }

  /**
   *
   */
  public function testDeriveKml() {

    $this->processor->deriveKml(array(), 'KML', $this->source_file, 'kml.xml');
  }

  /**
   *
   */
  public function testDeriveJson() {

    $this->processor->deriveJson(array(), 'JSON', $this->source_file, 'geojson.json');
  }

  protected function tearDown() {

    if(file_exists($this->geo_json_file)) {

      unlink($this->geo_json_file);
    }
  }
}

?>
