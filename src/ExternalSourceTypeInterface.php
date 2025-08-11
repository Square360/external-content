<?php

declare(strict_types=1);

namespace Drupal\external_content;

use Drupal\Core\Link;

/**
 * Interface for external_source_type plugins.
 */
interface ExternalSourceTypeInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Provides a form for configuring the external source type.
   *
   * @param array &$form_container
   *   The form container.
   * @param array &$plugin_configuration
   *   The plugin configuration.
   *
   * @return array
   *   The form array.
   */
  public function externalSourceConfigForm(array &$form_container, array &$plugin_configuration);

  /**
   * Handles autocomplete functionality for this external source type.
   *
   * @param \Drupal\external_content\Entity\ExternalContentSource $source
   *   The external content source entity.
   * @param string $input
   *   The user input string.
   *
   * @return array
   *   Array of autocomplete results with 'value' and 'label' keys.
   */
  public function handleAutocomplete($source, string $input): array;

  /**
   * Gets content from the external source.
   *
   * @param \Drupal\external_content\Entity\ExternalContentSource $source
   *   The external content source entity.
   * @param string|array $id
   *   Entity id(s) (nid, tid, or -1 for most recent). Can be a single ID or array of IDs.
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   External content data.
   */
  public function getContent($source, $id, int $limit = 1);

  /**
   * Parses content from the external source response.
   *
   * @param mixed $originalData
   *   The raw response data from the external source.
   *
   * @return array
   *   Array of parsed content items.
   */
  public function parseContent($originalData): array;

  /**
   * Gets a link to an external entity.
   *
   * @param mixed $doc
   *   The document/entity data.
   *
   * @return \Drupal\Core\Link
   *   Drupal Link object to the external entity.
   */
  public function getLinkToEntity(mixed $doc): Link;

}
