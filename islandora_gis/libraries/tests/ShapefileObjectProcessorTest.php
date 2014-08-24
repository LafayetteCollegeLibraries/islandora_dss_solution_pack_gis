<?php

require dirname(__DIR__) . '/ShapefileProcessor.php';

/**
 * Tests for ShapefileProcessor
 * @author griffinj@lafayette.edu
 *
 */

class MockFedoraObject implements ArrayAccess {

  /**
   * The identifier of the object.
   *
   * @var string
   */
  public $id;

  /**
   * Constructoman!
   */
  public function __construct($id) {

    $this->id = $id;
  }

  /**
   * @see ArrayAccess::offsetExists
   */
  public function offsetExists($offset) {
    return isset($this->datastreams[$offset]);
  }

  /**
   * @see ArrayAccess::offsetGet
   */
  public function offsetGet($offset) {

    /*
    if ($this->offsetExists($offset)) {
      return $this->datastreams[$offset];
    }
    else {
      return FALSE;
    }
    */
    return (object) array('content' => NULL);
  }

  /**
   * @see ArrayAccess::offsetSet
   */
  public function offsetSet($offset, $value) {
    trigger_error("Datastreams must be added though the NewFedoraObect->ingestDatastream() function.", E_USER_WARNING);
  }

  /**
   * @see ArrayAccess::offsetUnset
   */
  public function offsetUnset($offset) {
    $this->purgeDatastream($offset);
  }
}

class ShapefileObjectProcessorTest extends PHPUnit_Framework_TestCase {

  protected $processor;

  protected function setUp() {

    // @todo Refactor for Mocking FedoraObject
    //$this->object = $this->getMock('FedoraObject');
    //$this->object = (object) array('id' => 'gis:testObject');
    $this->object = new MockFedoraObject('gis:testObject');

    $fixture_shape_file_path = dirname(__DIR__) . '/tests/fixtures/test.zip';
    $shape_file_path = '/tmp/' . preg_replace('/[\s:]/', '_', $this->object->id) . '_SHP.zip';
    copy($fixture_shape_file_path, $shape_file_path);

    $this->shape_file_path = $shape_file_path;

    // This assumes that the Module has been properly installed (i. e. that the proper Node.js dependencies are present)
    $this->processor = new ShapefileObjectProcessor($this->object, $this->shape_file_path, '/usr/bin/env ogr2ogr',
						    dirname(__DIR__) . '/js/node_modules/topojson/bin/topojson',
						    dirname(__DIR__) . '/js/node_modules/topojson/bin/topojson-geojson');

    $this->gml_file = '/tmp/gis_testObject_SHP/GlendaleAZ_Council_Districts.gml.xml';
    $this->kml_file = '/tmp/gis_testObject_SHP/GlendaleAZ_Council_Districts.kml.xml';
    $this->geo_json_file = dirname(__DIR__) . '/tests/fixtures/test.geojson.json';
    $this->topo_json_file = dirname(__DIR__) . '/tests/fixtures/test.topojson.json';
  }

  /**
   *
   */
  public function testDeriveGml() {

    $output = $this->processor->deriveGml();
    $this->assertFileEquals($this->gml_file, $output);
  }

  /**
   *
   */
  public function testDeriveKml() {

    $output = $this->processor->deriveKml();
    $this->assertFileEquals($this->kml_file, $output);
  }

  /**
   *
   */
  public function testDeriveJson() {

    $output = $this->processor->deriveJson(TRUE);
    $this->assertFileEquals($this->geo_json_file, $output);
  }

  public function testSimplify() {

    //test_large.topojson.json
    $this->large_geojson_file = dirname(__DIR__) . '/tests/fixtures/test_large.geojson.json';

    //$this->large_object = (object) array('id' => 'gis:testLargeObject');
    $this->large_object = new MockFedoraObject('gis:testLargeObject');

    $fixture_large_shape_file_path = dirname(__DIR__) . '/tests/fixtures/test_large.zip';
    $large_shape_file_path = '/tmp/' . preg_replace('/[\s:]/', '_', $this->large_object->id) . '_SHP.zip';
    copy($fixture_large_shape_file_path, $large_shape_file_path);

    $this->large_shape_file_path = $large_shape_file_path;

    $this->processor = new ShapefileObjectProcessor($this->large_object, $this->large_shape_file_path, '/usr/bin/env ogr2ogr',
						    dirname(__DIR__) . '/js/node_modules/topojson/bin/topojson',
						    dirname(__DIR__) . '/js/node_modules/topojson/bin/topojson-geojson');

    $output = $this->processor->deriveJson();
    $this->assertFileEquals($this->large_geojson_file, $output);
  }

  public function testDeriveTopoJson() {

    $output = $this->processor->deriveJson();
    $this->assertFileEquals($this->topo_json_file, $output);
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
}

?>
