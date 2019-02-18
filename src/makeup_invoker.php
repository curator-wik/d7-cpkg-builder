<?php

function build(DOMElement $delta_version, DOMElement $latest_version) {
  $build_dir = setup_build_dir($delta_version, $latest_version);
  $latest_identifier = $latest_version->getElementsByTagName('version')->item(0)->nodeValue;
  $delta_identifier = $delta_version->getElementsByTagName('version')->item(0)->nodeValue;
  if (! invoke_makeup($build_dir)) {
    fwrite(STDERR, "Build failed for ${delta_identifier}->${latest_identifier}\n");
    exit(1);
  }

  // Move the resulting build artifact into repository dir
  if (!rename("${build_dir}/Drupal7.x-${latest_identifier}.cpkg.zip", "repo/Drupal${delta_identifier}-${latest_identifier}.cpkg.zip")) {
    fwrite(STDERR, "Failed moving \"${build_dir}/Drupal7.x-${latest_identifier}.cpkg.zip\" to repo\n");
    exit(1);
  }

  rmrdir($build_dir);
}

function setup_build_dir(DOMElement $delta_version, DOMElement $latest_version) {
  $dir = sys_get_temp_dir() . '/d7_cpkg_build_' . time();
  mkdir($dir);

  $runcwd = getcwd();
  chdir($dir);
  $skel = ['application' => "Drupal 7.x\n", 'component' => "core\n", 'package-format-version' => "1.0\n"];
  foreach ($skel as $filename => $data) {
    file_put_contents($filename, $data);
  }

  $latest_identifier = $latest_version->getElementsByTagName('version')->item(0)->nodeValue;
  $delta_identifier = $delta_version->getElementsByTagName('version')->item(0)->nodeValue;
  file_put_contents('version',"${latest_identifier}\n");
  file_put_contents('prev-versions-inorder', "${delta_identifier}\n");
  mkdir('release_trees');

  chdir($runcwd);
  ensure_release($delta_version);
  ensure_release($latest_version);

  symlink(realpath("cpkg_d7_release_cache/${latest_identifier}"), "${dir}/release_trees/${latest_identifier}");
  symlink(realpath("cpkg_d7_release_cache/${delta_identifier}"), "${dir}/release_trees/${delta_identifier}");

  return $dir;
}

function ensure_release(DOMElement $release) {
  if (!is_dir('cpkg_d7_release_cache')) {
    mkdir('cpkg_d7_release_cache');
  }

  $target = 'cpkg_d7_release_cache/' . $release->getElementsByTagName('version')->item(0)->nodeValue;
  if (! is_dir($target) || ! is_file("${target}/index.php")) {
    download_release($release, $target);
  }
}

function download_release(DOMElement $release, $target) {
  $download_url = $release->getElementsByTagName('download_link')->item(0)->nodeValue;
  if (!copy($download_url, "${target}.tar.gz")) {
    fwrite(STDERR, "Failed downloading ${download_url}\n");
    exit(1);
  }
  if (! is_dir($target)) mkdir($target);
  $wd = getcwd();
  chdir($target);
  if (!cmd("tar --strip-components=1 -zxf ../../${target}.tar.tz")) {
    fwrite(STDERR, "Failed extracting to ${target}\n");
    exit(1);
  }
  chdir($wd);
}

function invoke_makeup(string $build_dir) {
  if(cmd('php ' . __DIR__ . "/../vendor/bin/makeup.php $build_dir") !== 0) {
    return false;
  }
  return true;
}

/**
 * @param string $cmd
 * @return int
 *   exit code
 */
function cmd($cmd) {
  $pipes = [];
  $h_p = proc_open($cmd, [], $pipes);
  if (is_resource($h_p)) {
    return proc_close($h_p);
  } else {
    return -1;
  }
}