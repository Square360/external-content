<?php

declare(strict_types=1);

namespace Drupal\external_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\external_content\ExternalContentJsonApi;

/**
 * Base class for external_source_type plugins.
 */
abstract class ExternalSourceTypePluginBase extends PluginBase implements ExternalSourceTypeInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function handleAutocomplete($source, string $input): array;

  /**
   * {@inheritdoc}
   */
  abstract public function getContent($source, $id, int $limit = 1);

  /**
   * {@inheritdoc}
   */
  abstract public function parseContent($originalData): array;

  /**
   * {@inheritdoc}
   */
  abstract public function getLinkToEntity(mixed $doc): Link;

  /**
   * Extracts the domain from a resource URL.
   *
   * @param \Drupal\external_content\Entity\ExternalContentSource $source
   *   The external content source entity.
   *
   * @return string
   *   The domain extracted from the resource URL.
   */
  protected function getDomain($source): string {
    $resource_url = $source->getResource();
    $parsed_url = parse_url($resource_url);

    if (isset($parsed_url['host'])) {
      $domain = $parsed_url['host'];
      // Remove 'www.' if present
      if (strpos($domain, 'www.') === 0) {
        $domain = substr($domain, 4);
      }
      return $domain;
    }

    return '';
  }

  /**
   * Wrapper for altering request data before making JSON API calls.
   *
   * @param array &$query
   *   Query parameters (passed by reference to allow alteration).
   * @param array &$headers
   *   Headers array (passed by reference to allow alteration).
   * @param \Drupal\external_content\Entity\ExternalContentSource $source
   *   The external content source entity.
   * @param string $function
   *   The name of the calling function (e.g., 'getContent', 'handleAutocomplete').
   */
  protected function alterRequest(array &$query = [], array &$headers = [], $source = NULL, string $function = '') {
    // Allow modules to alter headers.
    \Drupal::service('module_handler')->alter('external_content_headers', $headers, $source, $function);

    // Allow modules to alter the query.
    \Drupal::service('module_handler')->alter('external_content_query', $query, $source, $function);
  }

}
