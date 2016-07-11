<?php

namespace Drupal\Tests\wunderstatus\Unit;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drupal\wunderstatus\WunderstatusService;
use Drupal\Tests\UnitTestCase;
use Drupal\wunderstatus\WunderstatusInfoCollector;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @group wunderstatus
 */
class WunderstatusServiceTest extends UnitTestCase {
  
  /** @var Client */
  private $client;

  /** @var ContainerInterface */
  private $container;

  /** @var LoggerChannel */
  private $logger;

  /** @var StateInterface */
  private $state;

  /** @var WunderstatusInfoCollector */
  private $wunderstatusInfoCollector;
  
  /** @var WunderstatusService */
  private $wunderstatusService;
  
  public function setUp() {
    parent::setUp();

    $this->client = $this->prophesize(Client::class);
    $this->logger = $this->prophesize(LoggerChannel::class);
    $this->wunderstatusInfoCollector = $this->prophesize(WunderstatusInfoCollector::class);

    $this->state = $this->prophesize(StateInterface::class);
    $this->state->get('wunderstatus_key')->willReturn('Key');
    $this->state->get('wunderstatus_manager_endpoint_url')->willReturn('http://www.example.com/');

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->container->get('state')->willReturn($this->state);
    \Drupal::setContainer($this->container->reveal());
    
    $this->wunderstatusService = new WunderstatusService(
      $this->client->reveal(),
      $this->logger->reveal(),
      $this->wunderstatusInfoCollector->reveal()
    );
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldLogWarningAndReturnFalseWhenAuthenticationKeyIsNotSet() {
    $this->state->get('wunderstatus_key')->willReturn(NULL);

    $this->logger->warning('Wunderstatus authentication key is not set.')->shouldBeCalled();

    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertFalse($response);
  }

  /**
   * @test
   */
  public function sendModuleInfoShouldLogWarningAndReturnFalseWhenManagerEndpointUrlIsNotSet() {
    $this->state->get('wunderstatus_manager_endpoint_url')->willReturn(NULL);

    $this->logger->warning('Wunderstatus manager endpoint URL is not set.')->shouldBeCalled();

    $response = $this->wunderstatusService->sendModuleInfo();

    $this->assertFalse($response);
  }
}