<?php

  /**
   * @file The ShapefileProcessor class (for the generation of Shapefile derivatives)
   *
   * @author griffinj@lafayette.edu
   */

  /**
   * This Class implements the methods defined in the Islandora GIS Shapefile Content Model
   *
   */
class Ogre {
  
  private $uri;

  /**
   * Constructor
   *
   * @param string $uri
   *
   */
  function Ogre($uri = 'http://localhost:3000') {
    
    $this->uri = $uri;
  }

  /**
   * POST requests
   *
   * @param string $url
   * @param array $data
   * @param string $download_path
   * @return string result of the cURL session
   * @todo Abstract and refactor for Ips::post()
   *
   */
  private function post($url, $data, $download_path = NULL) {
    
    // Instantiate the cURL handler
    $ch = curl_init();
    $encoded_data = http_build_query($data);

    // Set the options for the POST request
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);

    // Set the download path for the response
    if($download_path) {

      $f = fopen($download_path, 'wb');
      curl_setopt($ch, CURLOPT_FILE, $f);
    }

    // Transmit the POST request
    $result = curl_exec($ch);

    // Close the file buffer
    if(isset($f)) {

      fclose($f);
    }

    // Close the connection
    curl_close($ch);

    return $result;
  }

  /**
   * The method wrapping the "convert" route for the ogre Node.js application
   *
   * @param string $source_file_path
   * @param string $response_file_path
   * @return string The cURL session results from the POST request to ogre
   *
   */
  public function convert($source_file_path, $response_file_path = NULL) {

    $response = $this->post($this->uri . '/convert', array('file_contents' => '@' . $shape_file_path), $json_file_path);

    return $response;
  }
  }