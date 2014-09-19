<?php

  /**
   * @author griffinj@lafayette.edu
   * The unit tests for the Islandora GeoServer Cartaro API
   *
   */

require dirname(__DIR__) . '/CartaroFeature.php';
use IslandoraGeoServer\Cartaro\Feature;

class CartaroFeatureTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->feature = new Feature('shape-zip', 'http://rhodes0.stage.lafayette.edu:8080', '/geoserver-sqlite-2.5');
  }

  public function testRead() {

    $this->feature->read(array('typeName' => 'States_CottonSeedMills_1860_WGS84'));
  }

}