<?php

declare(strict_types=1);

namespace Drupal\external_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\external_content\Attribute\ExternalSourceType;

/**
 * ExternalSourceType plugin manager.
 */
final class ExternalSourceTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ExternalSourceType', $namespaces, $module_handler, ExternalSourceTypeInterface::class, ExternalSourceType::class);
    $this->alterInfo('external_source_type_info');
    $this->setCacheBackend($cache_backend, 'external_source_type_plugins');
  }

}
