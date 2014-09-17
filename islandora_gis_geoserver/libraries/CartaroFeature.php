<?php namespace IslandoraGeoServer;

include_once __DIR__ . "/../vendor/autoload.php";

class CartaroFeature {

  public $url;

  public function __construct($user, $pass,
			      $outputFormat = 'shape-zip',
			      $url = 'http://localhost:8080/geoserver/wfs',
			      $service = 'wfs',
			      $version = '2.0.0',
			      $request = 'GetFeature') {

    /*
http://example.com/geoserver/wfs?
  service=wfs&
  version=2.0.0&
  request=GetFeature&
     */

    $this->url = $url;
    
    
  }

  public function read($typeName = '',
		       $maxFeatures = '',
		       $count = '',
		       $sortBy = '',
		       $featureId = '',
		       $propertyName = array(),
		       $srsName = '',
		       $bbox = array()) {

    $params = array();

    $params['propertyName'] = implode(',', $bbox);
    $params['bbox'] = implode(',', $bbox);
  }
}
