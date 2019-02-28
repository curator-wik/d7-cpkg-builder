<?php

function write_travis_deployment_file($included_releases, $latest_release) {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../assets');
  $twig = new \Twig\Environment($loader);
  $template = $twig->load('.travis.yml.twig');

  // Indent all lines by 6 spaces for yaml multiline block
  $testcode = base64_encode(file_get_contents(__DIR__ . '/../assets/CpkgValidateTest.php'));

  $latest_release_array = releaseXmlToComparableArray($latest_release);
  $latest_release_array['url'] = $latest_release->getElementsByTagName('download_link')->item(0)->nodeValue;
  $delta_releases_array = [];
  foreach ($included_releases as $release) {
    $ra = releaseXmlToComparableArray($release);
    $ra['url'] = $release->getElementsByTagName('download_link')->item(0)->nodeValue;
    $delta_releases_array[] = $ra;
  }
  // Travis CI builds in the order of the env matrix; get newer updates out 1st.
  $delta_releases_array = array_reverse($delta_releases_array);

  file_put_contents('repo/.travis.yml', $template->render(
    [
      'current_version' => $latest_release_array['version'],
      'current_version_link' => $latest_release_array['url'],
      'delta_releases' => $delta_releases_array,
      'test_class_encoded' => $testcode
    ]
  ));

  $copy_files = ['travis.sh', 'secret_identity_file.enc'];
  foreach ($copy_files as $filename) {
    copy(__DIR__ . "/../assets/${filename}", "repo/${filename}");
  }
}
