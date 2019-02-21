<?php


namespace Curator\Tests\Integration\Cpkg;

use Curator\IntegrationConfig;
use Curator\Tests\Functional\Util\Session;
use Curator\Tests\Integration\IntegrationWebTestCase;
use Curator\Tests\Shared\Traits\Cpkg\WebTestCaseCpkgApplierTrait;
use Silex\Application;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;

/**
 * Class DrupalUpgradeTest
 *   Runs a complete Drupal 7.53 -> 7.54 upgrade, and verifies the result
 *   against another copy of Drupal 7.54 in the test container.
 */
class CpkgValidateTest extends IntegrationWebTestCase {
  use WebTestCaseCpkgApplierTrait;

  protected function getTestSiteRoot() {
    return getenv('TEST_SITE_ROOT');
  }

  public function getTestIntegrationConfig()
  {
    // Force autodetection of drupal 7
    return parent::getTestIntegrationConfig()->setCustomAppTargeter(null);
  }

  public function testDrupal7Upgrade() {
    $client = self::createClient();
    /**
     * @var SessionInterface $session
     */
    $session = $this->app['session'];
    // This test class has no unauthenticated tests.
    Session::makeSessionAuthenticated($session);

    $cj = $client->getCookieJar();
    $session_cookie = new Cookie($this->app['session']->getName(), $this->app['session']->getId());
    $cj->set($session_cookie);
    $v1 = getenv('DELTA_VERSION');
    $v2 = getenv('CURRENT_VERSION');
    $this->runBatchApplicationOfCpkg("Drupal${v1}-${v2}.cpkg.zip", $client);

    $this->verifyTreeMatchesCurrent("Post-updated delta version ${v1} does not match latest version source tree");
  }

  // Function name needed as-is to override
  protected function verifyTreeMatchesCurrent($message) {
    $d1 = getenv('TEST_SITE_ROOT');
    $d2 = 'current_version';
    $diff = `/usr/bin/diff --brief -r $d1 $d2 2>&1 | grep -Fv '.curator-data'`;
    $this->assertEquals(
      '',
      trim($diff),
      $message
    );

  }
}
