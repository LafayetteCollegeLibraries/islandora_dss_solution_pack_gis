<?php

  /**
   * @file The ShapefileProcessor class (for the generation of Shapefile derivatives)
   *
   * @author griffinj@lafayette.edu
   */

include 'vendor/autoload.php';

/**
 * FgdcProcessor Class implements the methods defined in the Islandora GIS Shapefile Content Model
 *
 */
class FgdcProcessor {

  const FGDC_SCHEMA_URI = 'https://www.fgdc.gov/schemas/metadata/fgdc-std-001-1998.xsd';
  const MODS_SCHEMA_URI = 'http://www.loc.gov/standards/mods/v3/mods-3-5.xsd';

  private $xsltproc_bin_path;
  private $xsl_file_path;

  /**
   * Constructor
   * @param string $xsltproc_bin_path The path to BASH wrapper script for the Saxon XSLT processor
   *
   */
  public function __construct($xsl_file_path = 'xsl/fgdc2mods.xsl', $xsltproc_bin_path = '/usr/bin/java -jar /usr/share/java/saxon.jar') {

    $this->xsl_file_path = $xsl_file_path;
    $this->xsltproc_bin_path = $xsltproc_bin_path;
  }

  /**
   * Validate the XML Document against a schema
   *
   * @param string $doc_file_path The file path to the XML Document being validated
   * @param string $doc_schema_uri The URI for the XML Schema Document
   * @return string The results of the validation
   * @access private
   *
   */
  protected function validate($doc_file_path, $doc_schema_uri) {

    $doc = new DOMDocument();
    $doc->load($doc_file_path);

    return $doc->schemaValidate($doc_schema_uri);
  }

  /**
   * Placeholder method implemented to ensure that this is a well-formed FGDC Document being transformed
   * @todo Resolve by ensuring that the FGDC Document is validated against the XSD
   *
   * @param string $doc_file_path The file path to the XML Document being validated
   * @return string The results of the validation
   * @access private
   *
   */
  protected function is_fgdc_doc($doc_file_path) {

    $doc = simplexml_load_file($doc_file_path);
    
    // Ensure that both the <idinfo> and <metainfo> occur once within the Document
    return isset($doc->idinfo) and isset($doc->metainfo);
  }

  /**
   * Invoke the xsltproc binary within the local environment
   *
   * @return string The resulting value of the command-line invocation
   * @access protected
   *
   */
  protected function xsltproc() {

    $args = func_get_args();
    $returnValue = FALSE;

    $invocation = $this->xsltproc_bin_path . ' ' . implode(' ', $args);

    $returnValue = exec(escapeshellcmd($invocation));

    return $returnValue;
  }

  /**
   * Transform the FGDC Document into the MODS
   *
   * @param type $parameterArray
   * @param type $dsid
   * @param type $fgdc_file_path
   * @param type $file_ext
   * @return The transformed MODS Document
   */
  public function transform($parameterArray = NULL, $dsid, $fgdc_file_path, $file_ext) {

    // Ensure that the FGDC is valid...
    /**
     * Fails: DOMDocument::schemaValidate(): Element 'caldate': 'unknown' is not a valid value of the union type 'caldateType'.
     * @todo Identify and resolve
     *
     */
    //$this->validate($fgdc_file_path, self::FGDC_SCHEMA_URI);
    if(!$this->is_fgdc_doc($fgdc_file_path)) {

      throw new Exception("$fgdc_file_path is not a valid FGDC Document.");
    }

    // Construct the file path for the MODS Document
    $mods_file_path = preg_replace('/(.+?)\..+?\.xml$/', "\\1.$file_ext", $fgdc_file_path);

    // Perform the transformation
    $returnValue = $this->xsltproc('-o ' . $mods_file_path, $fgdc_file_path, $this->xsl_file_path);

    // Validate the MODS against the schema
    $this->validate($mods_file_path, self::MODS_SCHEMA_URI);

    if ($returnValue == '0') {

      // Ingest the GML Document into the "MODS" datastream
      $_SESSION['fedora_ingest_files'][$dsid] = $mods_file_path;
      return TRUE;
    } else {

      return $returnValue;
    }
  }
}
