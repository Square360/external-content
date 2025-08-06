<?php

namespace Drupal\external_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an external_content_source entity type.
 */
interface ExternalContentSourceInterface extends ConfigEntityInterface {

  /**
   * Returns ID.
   *
   * @return int|string|null
   *   ID.
   */
  public function getId();

  /**
   * Returns label.
   *
   * @return string
   *   Label.
   */
  public function getLabel();

  /**
   * Returns type.
   *
   * @return string
   *   External source type.
   */
  public function getType();

  /**
   * Returns cache timeout.
   *
   * @return int
   *   Cache timeout.
   */
  public function getCacheTimeout();

  /**
   * Returns resource.
   *
   * @return string
   *   Resource.
   */
  public function getResource();

  /**
   * Returns plugin configuration.
   *
   * @return array
   *   Plugin configuration array.
   */
  public function getPluginConfiguration();

  /**
   * Gets content from the external source via the configured plugin.
   *
   * @param int $id
   *   Entity id (nid, tid, or -1 for most recent).
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   External content data.
   */
  public function getContent($id, $limit = 1);

  /**
   * Gets lookup query via the configured plugin.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQuery($input): array;

}
