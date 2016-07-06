<?php

namespace Drupal\wunderstatus;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WunderstatusService {

  /** @var Client */
  protected $client;

  /** @var LoggerChannel */
  protected $logger;

  /** @var WunderstatusInfoCollector */
  protected $wunderstatusInfoCollector;

  public function __construct(Client $client, LoggerChannel $logger, WunderstatusInfoCollector $wunderstatusInfoCollector) {
    $this->client = $client;
    $this->logger = $logger;
    $this->wunderstatusInfoCollector = $wunderstatusInfoCollector;
  }

  /**
   * Sends information about enabled modules to manager site (excluding core
   * modules).
   * 
   * @return bool|mixed|\Psr\Http\Message\ResponseInterface
   */
  public function sendModuleInfo() {
    $result = FALSE;

    if (empty($this->getKey())) {
      $this->logger->warning('Wunderstatus authentication key is not set.');
    }
    elseif (empty($this->getManagerEndpointUrl())) {
      $this->logger->warning('Wunderstatus manager URL is not set.');
    }
    else {
      try {
        $result = $this->client->request('POST', $this->getManagerEndpointUrl(), ['body' => $this->buildRequestData()]);
        $this->logger->notice('Module information sent.');
      }
      catch (RequestException $e) {
        $this->logger->error($e->getResponse()->getBody());
        $result = FALSE;
      }
    }

    return $result;
  }

  private function getKey() {
    return \Drupal::state()->get('wunderstatus_key');
  }

  private function getManagerEndpointUrl() {
    return \Drupal::state()->get('manager_site_url');
  }

  private function buildRequestData() {
    return Json::encode([
      'key' => $this->getKey(),
      'modules' => $this->wunderstatusInfoCollector->getVersionInfo(),
      'siteName' => \Drupal::config('system.site')->get('name'),
      'siteUuid' => \Drupal::config('system.site')->get('uuid'),
    ]);
  }
}