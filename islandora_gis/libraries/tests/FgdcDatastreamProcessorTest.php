<?php

require dirname(__DIR__) . '/FgdcProcessor.php';

/**
 * Tests for FgdcProcessor
 * @author griffinj@lafayette.edu
 *
 */

class FgdcProcessorTest extends PHPUnit_Framework_TestCase {

  protected $processor;

  protected function setUp() {

    //$this->processor = new FgdcProcessor( dirname(__DIR__) . '/bin/xsltproc-saxon', dirname(__DIR__)  . '/xsl/fgdc2mods.xsl'  );
    //$this->processor = new FgdcProcessor();

    $this->source_file = dirname(__DIR__) . '/tests/fixtures/test.fgdc.xml';
    $this->mods_file = dirname(__DIR__) . '/tests/fixtures/test.mods.xml';
    $fgdc_ds = (object) array('id' => 'FGDC',
			      'content' => file_get_contents($this->source_file));
    
    $this->processor = new FgdcDatastreamProcessor($fgdc_ds, dirname(__DIR__)  . '/xsl/fgdc2mods.xsl');
  }

  /**
   *
   */
  public function testTransform() {

    $this->processor->transform();
  }

  protected function tearDown() {

  }
}

?>
