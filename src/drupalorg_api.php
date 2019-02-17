<?php

/**
 * @return \DOMNodeList
 */
function get_release_info() {
  $xmlstr = file_get_contents('https://updates.drupal.org/release-history/drupal/7.x');
  $xmldom = new DOMDocument();
  $load = $xmldom->loadXML($xmlstr);
  if ($load === false) {
    throw new RuntimeException('Failed loading release information xml from drupal.org');
  }

  // We only ever care about the release info, zero in on and return that.
  $xpath = new DOMXPath($xmldom);
  $releases = $xpath->query('//releases/release');
  if ($releases->length > 1) {
    return $releases;
  } else {
    throw new RuntimeException('XML data from drupal.org contained no Drupal 7 releases?');
  }
}
