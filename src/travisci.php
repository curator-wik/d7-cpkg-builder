<?php

function write_travis_deployment_file($included_releases) {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../assets');
  $twig = new \Twig\Environment($loader);
  $template = $twig->load('.travis.yml.twig');

  // Indent all lines by 2 spaces for yaml multiline block
  $testcode = implode("\n  ", explode("\n", file_get_contents(__DIR__ . '/../assets/CpkgValidateTest.php')));
  file_put_contents('repo/.travis.yml', $template->render(
    ['test_class_code' => $testcode]
  ));
}