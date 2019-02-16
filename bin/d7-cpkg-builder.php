<?php

require '../vendor/autoload.php';

register_shutdown_function(function() {
  state_end_save(empty($GLOBALS['state']) ? null : $GLOBALS['state']);
});
$GLOBALS['state'] = state_startup_get();

$release = get_release_info();

if (latest_release_has_changed($release)) {
  echo "Detected new release yay";
  $GLOBALS['state']['last_processed_release'] = releaseXmlToComparableArray($release);
}

function latest_release_has_changed($release) {
  $releaseArray = releaseXmlToComparableArray($release);
  $lastProcessedArray = $GLOBALS['state']['last_processed_release'];
  return count(array_diff_assoc($releaseArray, $lastProcessedArray)) > 0;
}

function releaseXmlToComparableArray(\DOMElement $release) {
  return [
    'date' => $release->getElementsByTagName('date')->item(0),
    'filesize' => $release->getElementsByTagName('filesize')->item(0),
    'mdhash' => $release->getElementsByTagName('mdhash')->item(0),
    'version' => $release->getElementsByTagName('version')->item(0),
  ];
}