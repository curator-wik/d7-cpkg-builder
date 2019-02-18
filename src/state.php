<?php

/**
 * Get the state from consul if a lock was acquired, otherwise exit.
 *
 * @return array
 */
function state_startup_get() {
  $sf = new \SensioLabs\Consul\ServiceFactory();
  /** @var \SensioLabs\Consul\Services\SessionInterface $session */
  $session = $sf->get(\SensioLabs\Consul\Services\SessionInterface::class);
  /** @var \SensioLabs\Consul\Services\KVInterface $kv */
  $kv = $sf->get(\SensioLabs\Consul\Services\KVInterface::class);

  // start a session
  $sessionId = json_decode($session->create(null, ['TTL' => 15 * 60])->getBody())->ID;
  $lockAcquired = json_decode($kv->put('cpkg-builder/drupal/7/lock', 'initialized', ['acquire' => $sessionId])->getBody());

  $GLOBALS['consul_global_session_id'] = $sessionId;

  if ($lockAcquired !== true) {
    fwrite(STDERR, "Lock not acquired from consul\n");
    exit(1);
  }

  /** @var \GuzzleHttp\Psr7\Response $stateResponse */
  try {
    $stateResponse = $kv->get('cpkg-builder/drupal/7/state', ['raw' => 1]);
  } catch (\SensioLabs\Consul\Exception\ClientException $e) {
    if ($e->getCode() === 404) {
      fwrite(STDERR, "No existing state file found, making a new one.\n");
      return [
        'last_processed_release' => [
          'date' => 0,
          'filesize' => 0,
          'mdhash' => 'invalid',
          'version' => '0',
        ],
      ];
    } else {
      fwrite(STDERR, "Unable to retrieve state from consul: " . $e->getMessage() . "\n");
      exit(1);
    }
  }

  if ($stateResponse->getStatusCode() !== 200) {
    fwrite(STDERR, "Unable to retrieve state from consul: " . $stateResponse->getReasonPhrase() . "\n");
    exit(1);
  } else {
    return json_decode($stateResponse->getBody(), true);
  }
}

function state_refresh_lock() {
  fwrite(STDERR, "TODO: refresh consul lock\n");
}

/**
 * Save the state back to consul and release lock.
 *
 * This is registered as a shutdown function.
 *
 * @param array|null $state
 */
function state_end_save($state) {
  $sf = new \SensioLabs\Consul\ServiceFactory();
  /** @var \SensioLabs\Consul\Services\SessionInterface $session */
  $session = $sf->get(\SensioLabs\Consul\Services\SessionInterface::class);
  /** @var \SensioLabs\Consul\Services\KVInterface $kv */
  $kv = $sf->get(\SensioLabs\Consul\Services\KVInterface::class);

  if (is_array($state)) {
    $saveResult = json_decode($kv->put('cpkg-builder/drupal/7/state', json_encode($state))->getBody());
    if ($saveResult !== true) {
      fwrite(STDERR, "WARNING: failed to save updated state to consul!\n");
    }
  }

  $session->destroy($GLOBALS['consul_global_session_id']);
}
