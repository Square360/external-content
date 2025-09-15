<?php

declare(strict_types=1);

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_content\Attribute\ExternalSourceType;
use Drupal\external_content\ExternalContentJsonApi;
use Drupal\external_content\ExternalSourceTypePluginBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the external_source_type.
 */
#[ExternalSourceType(
  id: 'jsonapi_title',
  label: new TranslatableMarkup('JSONAPI by Title'),
  description: new TranslatableMarkup('Selects JSONAPI entities by title.'),
)]
final class JsonApiTitle extends ExternalSourceTypePluginBase {
  use JsonApiSourceTrait;

  function externalSourceConfigForm(array &$form_container, array &$plugin_configuration): array {

    $includes = $plugin_configuration['includes'] = $plugin_configuration['includes'] ?? '';

    $form_container['includes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSONAPI Includes'),
      '#default_value' => $includes,
      '#description' => $this->t(
        "JSONAPI 'includes' to request related data along with entity"
      ),
      '#required' => FALSE,
    ];

    return $form_container;
  }

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete($source, string $input): array {
    $endpoint = $this->getLookupResource($source);
    $query = $this->getLookupQuery($source, $input);
    $headers = [];
    $json = $this->getJsonApiResponse($endpoint, $query, $headers, $source, 'handleAutocomplete')['data'] ?? [];
    $results = $this->buildAutocompleteResults($json);

    // Add "Most recent item" option for title-based sources
    if (stripos('Most recent item', $input) !== FALSE) {
      $val = $this->t('Most recent item(s) (:id)', [':id' => -1]);
      array_unshift($results, [
        'value' => $val,
        'label' => $val,
      ]);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent($source, $id, int $limit = 1) {
    // Convert single ID to array for consistency
    if (!is_array($id)) {
      $id = [$id];
    }

    if ($id && $id[0] !== "-1") {
      if (count($id) > 1) {
        return $this->getContentByMultipleNids($source, $id);
      }
      else {
        return $this->getContentByNid($source, $id[0]);
      }
    }
    else {
      return $this->getContentByRecency($source, $limit);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($originalData): array {
    return $originalData['data'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLookupResource($source): string {
    return $source->getResource();
  }

  /**
   * {@inheritdoc}
   */
  public function getLookupQuery($source, string $input): array {
    return [
      'filter[title][operator]' => 'CONTAINS',
      'filter[title][value]' => $input,
      'page[limit]' => 5,
    ];
  }

  /**
   * Get content by node ID.
   */
  protected function getContentByNid($source, $id) {
    $endpoint = $source->getResource();
    $query = [
      "filter[drupal_internal__nid]" => $id,
      'include' => $this->getIncludes($source),
    ];
    $headers = [];
    $this->alterRequest($query, $headers, $source, 'getContent');
    return ExternalContentJsonApi::getJsonApi($endpoint, $query, $headers);
  }

  /**
   * Get content by recency.
   */
  protected function getContentByRecency($source, $limit = 1, $extra_arguments = []) {
    $endpoint = $source->getResource();
    $query = array_merge([
      'sort' => '-created',
      'page[limit]' => $limit,
      'include' => $this->getIncludes($source),
    ], $extra_arguments);
    $headers = [];
    $this->alterRequest($query, $headers, $source, 'getContent');
    return ExternalContentJsonApi::getJsonApi($endpoint, $query, $headers);
  }

  /**
   * Get content by multiple node IDs.
   */
  protected function getContentByMultipleNids($source, array $ids) {
    $endpoint = $source->getResource();
    $query = [
      'filter[drupal_internal__nid][operator]' => 'IN',
      'include' => $this->getIncludes($source),
    ];

    // Add each ID as a separate value parameter
    foreach ($ids as $index => $nid) {
      $query["filter[drupal_internal__nid][value][$index]"] = $nid;
    }

    $headers = [];
    $this->alterRequest($query, $headers, $source, 'getContent');
    return ExternalContentJsonApi::getJsonApi($endpoint, $query, $headers);
  }


}
