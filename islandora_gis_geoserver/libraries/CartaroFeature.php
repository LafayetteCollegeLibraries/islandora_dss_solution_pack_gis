<?php
namespace IslandoraGeoServer\Cartaro;

include_once __DIR__ . "/../vendor/autoload.php";
use Guzzle\Http\Client;

class Feature {

  public $path;
  public $file_path;

  private $fh;

  //$user, $pass,
  public function __construct(
			      $outputFormat = 'shape-zip',
			      $host = 'http://localhost:8080',
			      $path = 'geoserver',
			      $service = 'wfs',
			      $version = '2.0.0',
			      $request = 'GetFeature',
			      $file_path = '/tmp/CartaroFeature_shapefile.zip') {

    $this->path = $path;
    $this->client = new Client($host);

    $this->params = array('service' => $service,
			  'version' => $version,
			  'request' => $request,
			  'outputFormat' => $outputFormat);

    $this->file_path;
    $this->fh = fopen($file_path, 'w');
  }

  public function read($params = array('typeName' => '',
				       'maxFeatures' => '',
				       'count' => '',
				       'sortBy' => '',
				       'featureId' => '',
				       'propertyName' => array(),
				       'srsName' => '',
				       'bbox' => array())) {

    $params = array_merge($this->params, $params);

    $request = $this->client->get('/' . $this->path . '/wfs?' . http_build_query($params),
				  array(),
				  array('save_to' => $this->fh));
    $response = $request->send();
  }

  public function __destruct() {

    fclose($this->fh);
    unlink($this->file_path);
  }
}
