<?php

/**
 * @author griffinj@lafayette.edu
 * The unit tests for the IslandoraGeoServer API
 *
 */

require dirname(__DIR__) . '/IslandoraGeoServerClient.php';

// use Guzzle\Tests\Server as Server;
// use GuzzleHttp\Subscriber\History;
use IslandoraGeoServer\IslandoraGeoServerSession as IslandoraGeoServerSession;
use IslandoraGeoServer\IslandoraGeoServerClient as IslandoraGeoServerClient;

use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

class IslandoraGeoServerClientTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    global $argv;
    $this->url = array_pop($argv);
    $this->pass = array_pop($argv);

    $this->session = new IslandoraGeoServerSession('admin', $this->pass, $this->url);

    $this->plugin = new Guzzle\Plugin\Mock\MockPlugin();

    $this->guzzle = new Guzzle\Http\Client();
    $this->guzzle->addSubscriber($this->plugin);
  }

  public function testCurl() {

    $username = 'admin';
    //$geoserver_url = 'http://rhodes0.stage.lafayette.edu:8080/geoserver-sqlite-2.5/';
    $file = '/tmp/geoserver_session_phpunit_delete_me_';

    $fields = 'username=' . urlencode($username) . '&password=' . urlencode($this->pass);
    $request = curl_init($this->url . 'j_spring_security_check');
    curl_setopt($request, CURLOPT_POST, TRUE);
    curl_setopt($request, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($request, CURLOPT_COOKIEJAR, $file);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_HEADER, TRUE);
    $header = curl_exec($request);
    $http_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
    curl_close($request);

    $cookies = IslandoraGeoServerSession::geoserver_parse_cookiefile($file);

    // Verify that the authentication was performed correctly
    if ($http_code >= 200 && $http_code < 400) {

      if (isset($cookies['JSESSIONID'])) {

	preg_match('/Location:(.*?)\n/', $header, $matches);
	$location_url = parse_url(array_pop($matches));
	$location_path = isset($location_url['query']) ? $location_url['query'] : '';

	$this->assertEquals(FALSE, strpos($location_path, 'error=true'));

	/*
	// Attempt to create the coverage from the file in the TIFF
	$headers = array('Content-type: image/tiff');

	//$file_name_with_full_path = realpath('./cea.tiff');
	//$file_name_with_full_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/cea.tif');
	$file_name_with_full_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
	$f = fopen($file_name_with_full_path, "rb");

	//$content = 'file=@' . urlencode($file_name_with_full_path);
	$data = http_build_query(array('file' => '@' . $file_name_with_full_path));
	//$data = 'file=@' . $file_name_with_full_path;
	//$data = array('file' => '@' . $file_name_with_full_path);

	$request = curl_init('http://rhodes0.stage.lafayette.edu:8080/geoserver-sqlite-2.5/rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff');
	//curl_setopt($request, CURLOPT_URL, 'http://rhodes0.stage.lafayette.edu:8080/geoserver-sqlite-2.5/rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff');
	//curl_setopt($request, CURLOPT_POST, 1);
	curl_setopt($request, CURLOPT_PUT, 1);
	//curl_setopt($request, CURLOPT_CUSTOMREQUEST, "PUT");
	//curl_setopt($request, CURLOPT_POSTFIELDS, $content);
	//curl_setopt($request, CURLOPT_POSTFIELDS, $data);
	curl_setopt($request, CURLOPT_INFILE, $f);
	curl_setopt($request, CURLOPT_INFILESIZE, filesize($file_name_with_full_path));

	curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($request, CURLOPT_COOKIEFILE, $file);
	//$header = curl_exec($request);
	$http_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
	curl_close($request);
	fclose($f);

	//$this->assertEquals('201', $http_code);
	*/
      }
    }
  }

  public function testGuzzle() {

    $cookiePlugin = new CookiePlugin(new ArrayCookieJar());

    // Add the cookie plugin to a client
    $client = new Client($this->url);
    $client->addSubscriber($cookiePlugin);

    // Listen for responses
    $mockPlugin = new Guzzle\Plugin\Mock\MockPlugin();
    $client->addSubscriber($mockPlugin);

    // Send the request with no cookies and parse the returned cookies
    //$request = $client->post('/geoserver-sqlite-2.5/j_spring_security_check')->setAuth('admin', end($argv));
    $request = $client->post('j_spring_security_check')->setPostField('username', 'admin')->setPostField('password', $this->pass);

    // Send the request again, noticing that cookies are being sent
    //$request = $client->get('http://www.yahoo.com/');
    $response = $request->send();

    $event_dispatcher = $request->getEventDispatcher();
    $listeners = $event_dispatcher->getListeners('request.sent');
    $listeners = array_filter($listeners, function($e) {

	return get_class(array_shift($e)) == 'Guzzle\Plugin\Cookie\CookiePlugin';
      });

    $cookie_listener = array_shift($listeners);
    $cookie_plugin = array_shift($cookie_listener);
    $cookie_jar = $cookie_plugin->getCookieJar();

    // Retrieve an index of Workspaces
    $request = $client->get('rest/workspaces.json');
    $response = $request->send();
    $payload = $response->json();
    print_r($payload);

    // Retrieve the default Workspaces
    $request = $client->get('rest/workspaces/default.json');
    $response = $request->send();
    $payload = $response->json();
    print_r($payload);

    $request = $client->get('rest/workspaces/default/datastores.json');
    $response = $request->send();
    $payload = $response->json();
    print_r($payload);

    $request = $client->get('rest/workspaces/default/coveragestores.json');
    $response = $request->send();
    $payload = $response->json();
    print_r($payload);

    /*
    // Add a coverage store
    $request = $client->post('rest/workspaces/default/coveragestores.json', array('content-type' => 'application/json'));
    $request->setBody(json_encode( array('coverageStore' => array('name' => 'test-coverage-store',
								  'workspace' => 'default',
								  'enable' => 'true'))));

    //$response = $request->send();
    //$payload = $response->json();
    print_r($payload);
    */

    // Add a coverage
    //$request = $client->post('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'application/zip'));
    /*
    $request = $client->put('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'image/tiff'),
			    //array('file' => '@eapl-sanborn-easton-1919_010_modified.tif'));
			    array('file' => '@cea.tif'));
    */
    /*
    $request = $client->put('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'application/zip'),
			    array('file' => '@cea.zip'));
    */

    //'http://digital.stage.lafayette.edu/islandora/object/islandora%3A69293/datastream/OBJ/view'
    //'https://digital.stage.lafayette.edu/islandora/object/islandora%3A69293'
    /*
      $response = $request->send();
      $payload = $response->json();
      print_r($payload);
    */

    try {

      /*
      // Add a coverage
      //$request = $client->post('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'application/zip'));
      $request = $client->put('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'image/tiff'),
			      //array('file' => '@eapl-sanborn-easton-1919_010_modified.tif'));
			      array('file' => '@cea.tif'));
      $response = $request->send();
      $payload = $response->json();
      print_r($payload);
      */
    } catch(Exception $e) {

    }

    // Add a coverage
    //$request = $client->post('rest/workspaces/default/coveragestores/test-coverage-store/file.geotiff', array('content-type' => 'application/zip'));

    /*
    $file_name_with_full_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
    $f = fopen($file_name_with_full_path, "rb");
    $request = $client->put('rest/workspaces/default/coveragestores/islandora:test/file.geotiff', array('content-type' => 'image/tiff'),
			    //array('file' => '@eapl-sanborn-easton-1919_010_modified.tif')
			    $f
			    );
    $response = $request->send();
    //$payload = $response->json();
    print_r($payload);
    fclose($f);

    // Delete a coverage store
    $request = $client->delete('rest/workspaces/default/coveragestores/islandora:test.json?recurse=true',
			       array('content-type' => 'application/json'));
    $response = $request->send();
    $payload = $response->json();
    print_r($payload);
    */

    /*
    // Add a layer
    */
  }

  public function testGet() {

    /*
    $this->plugin->addResponse(new Guzzle\Http\Message\Response(200));
    $this->client->get('path');

    $requests = $this->plugin->getReceivedRequests();
    $this->assertNotEmpty($requests);

    $request = array_pop($requests);
    $this->assertEquals('GET', $request->getMethod());
    $this->assertEquals('localhost', $request->getHost());
    $this->assertEquals('/geoserver/rest/path', $request->getPath());
    */
  }

  public function testAuth() {

    $this->client = new IslandoraGeoServerClient($this->session);
    $this->client->get('rest/workspaces/default.json');
  }

  public function testWorkspace() {

    $this->client = new IslandoraGeoServerClient($this->session);
    $this->client->workspace = 'default';
  }

  public function testCreateCoverage() {

    $this->client = new IslandoraGeoServerClient($this->session);
    //$workspace = $this->client->workspace('default');

    //$workspace->coverageStores['test1'];

    //$this->client->workspace = 'default';
    $workspace = $this->client->workspace('default');

    $file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
    $workspace->createCoverageStore('test2', $file_path);
    //$this->assertNull();
    //print get_class($workspace->coverageStores['test1']);
  }

  public function testDeleteCoverage() {

    $this->client = new IslandoraGeoServerClient($this->session);
    $workspace = $this->client->workspace('default');

    $file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/eapl-sanborn-easton-1919_010_modified.tif';
    $workspace->createCoverageStore('test3', $file_path);
    $workspace->deleteCoverageStore('test3');
  }

  /**
   * Testing the creation of data stores from compressed Shapefiles
   *
   */
  public function testCreateDataStore() {

    $this->client = new IslandoraGeoServerClient($this->session);
    $workspace = $this->client->workspace('default');

    $file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/libraries/tests/fixtures/test.zip';
    $workspace->createDataStore('test4', $file_path);
  }

  /**
   * Testing the deletion of data stores from compressed Shapefiles
   *
   */
  public function testDeleteDataStore() {

    $this->client = new IslandoraGeoServerClient($this->session);
    $workspace = $this->client->workspace('default');

    $file_path = '/var/www/drupal/sites/all/modules/islandora_dss_solution_pack_gis/islandora_gis_geoserver/libraries/tests/fixtures/test.zip';
    $workspace->createDataStore('test5', $file_path);
    $workspace->deleteDataStore('test5');
  }

  public function testPost() {

  }

  public function testPut() {

  }

  public function testDelete() {

  }

  protected function tearDown() {

  }

  }
