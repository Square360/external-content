<?php

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\external_content\ExternalContentJsonApi;

/**
 * Trait for shared JSON:API logic in ExternalSourceType plugins.
 */
trait JsonApiSourceTrait {

  /**
   * Build autocomplete results from JSON:API data.
   */
  protected function buildAutocompleteResults(array $json): array {
    $results = [];
    if ($json !== FALSE && !empty($json)) {
      foreach ($json as $result) {
        $drupal_id = !empty($result['attributes']['drupal_internal__nid'])
          ? $result['attributes']['drupal_internal__nid']
          : ($result['attributes']['drupal_internal__tid'] ?? '');
        $title = !empty($result['attributes']['title'])
          ? $result['attributes']['title']
          : ($result['attributes']['name'] ?? '');
        $results[] = [
          'value' => "$title ($drupal_id)",
          'label' => "$title ($drupal_id)",
        ];
      }
    }
    return $results;
  }

  /**
   * Build a Link object to an entity from JSON:API doc.
   */
  public function getLinkToEntity(mixed $doc): Link {
    $title = $doc['attributes']['title'] ?? $doc['attributes']['name'] ?? 'Untitled';
    $url_string = ExternalContentJsonApi::getUrlFromEntity($doc);
    $url = Url::fromUri($url_string);
    return Link::fromTextAndUrl($title, $url);
  }

  /**
   * Get includes from plugin configuration.
   */
  public function getIncludes($source): string {
    $plugin_config = $source->getPluginConfiguration();
    return $plugin_config['includes'] ?? '';
  }

  /**
   * Make a JSON:API request with standard pattern.
   */
  protected function getJsonApiResponse($endpoint, $query, $headers, $source, $function = 'getContent') {
    $this->alterRequest($query, $headers, $source, $function);
    return ExternalContentJsonApi::getJsonApi($endpoint, $query, $headers);
  }
}

