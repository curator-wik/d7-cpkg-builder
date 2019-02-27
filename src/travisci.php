<?php

function write_travis_deployment_file($included_releases) {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../assets');
  $twig = new \Twig\Environment($loader);
  $template = $twig->load('.travis.yml.twig');

  // Indent all lines by 6 spaces for yaml multiline block
  $testcode = base64_encode(file_get_contents(__DIR__ . '/../assets/CpkgValidateTest.php'));
  file_put_contents('repo/.travis.yml', $template->render(
    ['test_class_encoded' => $testcode]
  ));

  $copy_files = ['travis.sh', 'secret_identity_file.enc'];
  foreach ($copy_files as $filename) {
    copy(__DIR__ . "/../assets/${filename}", "repo/${filename}");
  }
}
