<?php

function write_travis_deployment_file() {
  $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../assets');
  $twig = new \Twig\Environment($loader);
  $template = $twig->load('.travis.yml.twig');

  file_put_contents('repo/.travis.yml', $template->render(
    ['test_class_code' => file_get_contents(__DIR__ . '/CpkgValidateTest.php')]
  ));
}