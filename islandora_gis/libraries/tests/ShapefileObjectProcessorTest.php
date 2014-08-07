<?php

require dirname(__DIR__) . '/ShapefileProcessor.php';

/**
 * Tests for ShapefileProcessor
 * @author griffinj@lafayette.edu
 *
 */

class ShapefileObjectProcessorTest extends PHPUnit_Framework_TestCase {

  protected $processor;

  protected function setUp() {

    // @todo Refactor for Mocking FedoraObject
    //$this->object = $this->getMock('FedoraObject');
    $this->object = (object) array('id' => 'gis:testObject');

    $fixture_shape_file_path = dirname(__DIR__) . '/tests/fixtures/test.zip';
    $shape_file_path = '/tmp/' . preg_replace('/[\s:]/', '_', $this->object->id) . '_SHP.zip';
    copy($fixture_shape_file_path, $shape_file_path);

    $this->shape_file_path = $shape_file_path;

    $this->processor = new ShapefileObjectProcessor($this->object, $this->shape_file_path);

    $this->gml_file = '/tmp/gis_testObject_SHP/GlendaleAZ_Council_Districts.gml.xml';
    $this->kml_file = '/tmp/gis_testObject_SHP/GlendaleAZ_Council_Districts.kml.xml';
    $this->geo_json_file = '/tmp/gis_testObject_SHP/GlendaleAZ_Council_Districts.geojson.json';
  }

  /**
   *
   */
  public function testDeriveGml() {

    $output = $this->processor->deriveGml();
    //$this->assertNotEquals('', $output);
    $this->assertFileEquals($this->gml_file, $output);
  }

  /**
   *
   */
  public function testDeriveKml() {

    $output = $this->processor->deriveKml();
    //$this->assertNotEquals('', $output);
    $this->assertFileEquals($this->kml_file, $output);
  }

  /**
   *
   */
  public function testDeriveJson() {

    $output = $this->processor->deriveJson();
    //$this->assertNotEquals('', $output);
    $this->assertFileEquals($this->geo_json_file, $output);
  }

  /**
   *
   *
   */
  public function testDerive() {

    foreach(array('GML', 'KML', 'JSON') as $ds_id) {

      $this->assertNotEquals(NULL, $this->processor->derive($ds_id));
    }
  }

  protected function tearDown() {

    if(file_exists($this->geo_json_file)) {

      unlink($this->geo_json_file);
    }
  }
}

?>
