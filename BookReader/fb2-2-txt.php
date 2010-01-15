<?php

// Load the XML source
$xml = new DOMDocument;
$xml->load($argv[1]);

$xsl = new DOMDocument;
$xsl->load('FB2_2_txt.xsl');

// Configure the transformer
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); // attach the xsl rules

echo $proc->transformToXML($xml);

?>