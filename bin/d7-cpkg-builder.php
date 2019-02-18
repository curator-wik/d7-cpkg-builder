<?php

require __DIR__ . '/../vendor/autoload.php';

if (! is_dir('repo/.git')) {
  fwrite(STDERR, "Need git repository to deposit build artifacts into at repo/\n");
  fwrite(STDERR, "A remote should be configured that the user can push to noninteractively.\n");
  exit(1);
}

register_shutdown_function(function() {
  state_end_save(empty($GLOBALS['state']) ? null : $GLOBALS['state']);
});
$GLOBALS['state'] = state_startup_get();

$releases = get_release_info();

// We assume the 1st release in order in the xml is the most recent.
$latest_release = $releases->item(0);

if (latest_release_has_changed($latest_release)) {
  // Detected a new release, how bout that!
  // Clean up old release artifacts for now-obsolete version
  $dh = opendir('repo');
  while (($dirent = readdir($dh)) !== false) {
    if (strncmp('.', $dirent, 1) !== 0) {
      unlink("repo/${dirent}");
    }
  }

  // Make new release artifacts to upgrade to current version
  for($i = $releases->length - 1; $i > 0; $i--) {
    $delta_release = $releases->item($i);
    if ($delta_release->getElementsByTagName('security')->length
      && $delta_release->getElementsByTagName('security')->item(0)->getAttribute('covered')) {
      build($delta_release, $latest_release);
    }
  }

  wrangle_git();

  $GLOBALS['state']['last_processed_release'] = releaseXmlToComparableArray($latest_release);
}

function latest_release_has_changed($release) {
  $releaseArray = releaseXmlToComparableArray($release);
  $lastProcessedArray = $GLOBALS['state']['last_processed_release'];
  return count(array_diff_assoc($releaseArray, $lastProcessedArray)) > 0;
}

function releaseXmlToComparableArray(\DOMElement $release) {
  return [
    'date' => $release->getElementsByTagName('date')->item(0)->nodeValue,
    'filesize' => $release->getElementsByTagName('filesize')->item(0)->nodeValue,
    'mdhash' => $release->getElementsByTagName('mdhash')->item(0)->nodeValue,
    'version' => $release->getElementsByTagName('version')->item(0)->nodeValue,
  ];
}

function wrangle_git() {
  $wd = getcwd();
  chdir('repo');
  if (cmd('git add -A') !== 0) {
    fwrite(STDERR, "git add -A failed\n");
    exit(1);
  }

  if (cmd('git log') === 0) {
    $amend = ' --amend ';
  } else {
    $amend = '';
  }

  if (cmd("git commit${amend} -m 'Single-commit, overwritten repo used for CI'") !== 0) {
    fwrite(STDERR, "git commit ${amend}... failed\n");
    exit(1);
  }
  if (cmd('git push --force -u origin master') !== 0) {
    fwrite(STDERR, "git push failed\n");
    exit(1);
  }
  chdir($wd);
}
