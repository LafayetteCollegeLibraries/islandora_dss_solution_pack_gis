<?php

namespace GeoServer;


/**
 * @see geoserver_resource
 *
 */

abstract class Resource {

  public $name;

  function __construct($client, $name) {

    $this->client = $client;
    $this->name = $name;

    $this->read();
  }

  private function request($method, $path) {

    $request_method_map = array('create' => 'post',
				'update' => 'put',
				'delete' => 'delete',
				'read' => 'get');

    if(array_key_exists($method, $request_method_map)) {

      throw new Exception("Operation $method not supported.");
    }

    call_user_func(array($client, $request_method_map[$method]), $path);
  }
}
