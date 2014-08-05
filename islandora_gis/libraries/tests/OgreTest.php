<?php

require dirname(__DIR__) . '/Ogre.php';

/**
 * Test for the Ogre
 * @author griffinj@lafayette.edu
 *
 */
class OgreTest extends PHPUnit_Framework_TestCase {

  protected $ogre;
  protected $source_file;

  protected function setUp() {

    $this->ogre = new Ogre();
    $this->source_file = dirname(__DIR__) . '/tests/fixtures/test.shp';
    $this->geo_json_file = dirname(__DIR__) . '/tests/fixtures/test.geojson.json';
  }

  //  public function convert($source_file_path, $response_file_path = NULL) {
  public function testConvert() {

    $this->ogre->convert($this->source_file, $this->geo_json_file);
  }
}

?>
