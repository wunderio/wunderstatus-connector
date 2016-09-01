<?php

namespace Drupal\wunderstatus;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Install\Tasks;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;

class WunderstatusInfoCollector {
  use StringTranslationTrait;

  /** @var ModuleHandlerInterface */
  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @return array Modules and core system versions. Includes:
   * - Drupal core version
   * - PHP version
   * - Database version
   */
  public function getVersionInfo() {
    $modules = $this->getNonCoreModules();

    $versions = [
      $this->getDrupalVersion(), 
      $this->getPhpVersion(),
      $this->getDatabaseSystemVersion()
    ];

    foreach ($modules as $module) {
      $versions[] = $module->getName() . ' ' . $this->getModuleVersion($module) . ' ' . $this->getModuleStatus($module);
    }

    return $versions;
  }

  /**
   * @return Extension[]
   */
  private function getNonCoreModules() {
    $modules = $this->moduleHandler->getModuleList();

    return array_filter($modules, function ($module) {
      /** @var $module Extension */
      return strpos($module->getPathname(), 'core') !== 0;
    });
  }
  
  private function getPhpVersion() {
    return 'PHP ' . phpversion();
  }

  private function getDrupalVersion() {
    return 'Drupal ' . \Drupal::VERSION;
  }

  protected function getDatabaseSystemVersion() {
    $class = Database::getConnection()->getDriverClass('Install\\Tasks');
    /** @var $tasks Tasks */
    $tasks = new $class();

    return $tasks->name() . ' ' . Database::getConnection()->version();
  }

  private function getModuleVersion(Extension $module) {
    $infoFile = $this->getInfoFile($module);
    $version = $this->t('Unspecified');

    foreach ($infoFile as $lineNumber => $line) {
      if (strpos($line, 'version:') !== FALSE) {
        $version = $this->parseVersion($line);
      }
    }

    return $version;
  }
  
  /**
   * @param Extension $module
   * @return array
   */
  protected function getInfoFile(Extension $module) {
    return file($module->getPathname());
  }

  private function parseVersion($versionString) {
    $version = str_replace('version:', '', $versionString);
    $version = str_replace("'", '', $version);

    return trim($version);
  }

  private function getModuleStatus(Extension $module) {
    $url = $this->buildUpdateUrl($module);
    $client = \Drupal::httpClient();
    $res = $client->request('GET', $url, [
        'headers' => [
          'Accept' => 'application/xml'
        ]
        ]);
    $data = $res->getBody();
    $release_information = $this->parseXml($data);
    return $release_information;
  }

  private function buildUpdateUrl(Extension $module) {
    $url = 'http://updates.drupal.org/release-history';
    $name = $module->getName();
    $url .= '/' . $name . '/' . \Drupal::CORE_COMPATIBILITY;
    return $url;
  }

/**
 * Parses the XML of the Drupal release history info files.
 *
 * @param $xml_data
 *   A raw XML string of available release data for a given project.
 *
 * @return
 *   Array of parsed data about releases for a given project, or NULL if there
 *   was an error parsing the string.
 */
  public function parseXml($xml_data) {
    try {
      $xml = new \SimpleXMLElement($xml_data);
    }
    catch (\Exception $e) {
      // SimpleXMLElement::__construct produces an E_WARNING error message for
      // each error found in the XML data and throws an exception if errors
      // were detected. Catch any exception and return failure (NULL).
      return NULL;
    }
    // If there is no valid project data, the XML is invalid, so return failure.
    if (!isset($xml->short_name)) {
      return NULL;
    }
    $data = array();
    foreach ($xml as $k => $v) {
      $data[$k] = (string) $v;
    }
    $data['releases'] = array();
    if (isset($xml->releases)) {
      foreach ($xml->releases->children() as $release) {
        $version = (string) $release->version;
        $data['releases'][$version] = array();
        foreach ($release->children() as $k => $v) {
          $data['releases'][$version][$k] = (string) $v;
        }
        $data['releases'][$version]['terms'] = array();
        if ($release->terms) {
          foreach ($release->terms->children() as $term) {
            if (!isset($data['releases'][$version]['terms'][(string) $term->name])) {
              $data['releases'][$version]['terms'][(string) $term->name] = array();
            }
            $data['releases'][$version]['terms'][(string) $term->name][] = (string) $term->value;
          }
        }
      }
    }
    return $data;
  }
}
