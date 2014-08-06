<?php

/**
 * @file
 * Defines the class IslandoraWebTestCase, which allows tests to access Fedora.
 *
 */

class IslandoraGisWebTestCase extends IslandoraWebTestCase {

  /**
   * Gets info to display to describe this test.
   *
   * @see IslandoraWebTestCase::getInfo()
   */
  public static function getInfo() {

    return array(
      'name' => 'Islandora GIS',
      'description' => 'Ensure that the hooks for ingestion/purge/modification are called at the appropriate times.',
      'group' => 'Islandora',
    );
  }

  /**
   * Sets up the Drupal filter to access this test Drupal instances database.
   *
   * @see IslandoraWebTestCase::setUp()
   */
  public function setUp() {

    $args = func_get_args();
    $args = (isset($args[0]) && is_array($args[0])) ? $args[0] : $args;
    // Always enable islandora.
    $args[] = 'islandora';

    parent::setUp($args);
  }

  /**
   * Restores the original Drupal filter, frees any allocated resources.
   *
   * @see IslandoraWebTestCase::tearDown()
   */
  public function tearDown() {

    parent::tearDown();
  }

  public function testIngest() {

    // Test ingesting with FedoraRepository::ingestObject().
    $object = $this->repository->constructObject('test:testIngestedObjectHook');
    $this->repository->ingestObject($object);
  }
}
