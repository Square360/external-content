<?php

declare(strict_types=1);

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_content\Attribute\ExternalSourceType;
use Drupal\external_content\ExternalSourceTypePluginBase;
use Drupal\external_content\ExternalContentJsonApi;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the external_source_type.
 */
#[ExternalSourceType(
  id: 'jsonapi_term',
  label: new TranslatableMarkup('JSONAPI by Term'),
  description: new TranslatableMarkup('Selects JSONAPI entities by taxonomy term.'),
)]
final class JsonApiTerm extends ExternalSourceTypePluginBase {
  use JsonApiSourceTrait;

  function externalSourceConfigForm(array &$form_container, array &$plugin_configuration): array {

    $includes = $plugin_configuration['includes'] ?? '';
    $term_resource = $plugin_configuration['term_resource'] ?? '';
    $term_field = $plugin_configuration['term_field'] ?? '';

    $form_container['includes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSONAPI Includes'),
      '#default_value' => $includes,
      '#description' => $this->t(
        "JSONAPI 'includes' to request related data along with entity"
      ),
      '#required' => FALSE,
    ];

    $form_container['term_resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Resource (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $term_resource,
      '#description' => $this->t("Resource from which to select filterable terms."),
      '#required' => FALSE,
    ];

    $form_container['term_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Field (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $term_field,
      '#description' => $this->t("Add a field name to determine which entity field on which to filter by term."),
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
    return $this->buildAutocompleteResults($json);
  }

  /**
   * {@inheritdoc}
   */
  public function getContent($source, $id, int $limit = 1) {
    // Convert single ID to array for consistency
    if (!is_array($id)) {
      $id = [$id];
    }
    return $this->getContentByTerm($source, $id, $limit);
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
    $plugin_config = $source->getPluginConfiguration();
    $term_resource = $plugin_config['term_resource'] ?? '';
    return !empty($term_resource) ? $term_resource : $source->getResource();
  }

  /**
   * {@inheritdoc}
   */
  public function getLookupQuery($source, string $input): array {
    return [
      "filter[name][operator]" => "CONTAINS",
      "filter[name][value]" => $input,
      'page[limit]' => 5,
    ];
  }

  /**
   * Get content by taxonomy term.
   */
  public function getContentByTerm($source, array $term_ids, $limit = 1) {
    $endpoint = $source->getResource();
    if (count($term_ids) > 1) {
      $query = $this->getContentByMultiTermQuery($source, $term_ids, $limit);
    }
    else {
      $query = $this->getContentByTermQuery($source, $term_ids, $limit);
    }
    $headers = [];
    $this->alterRequest($query, $headers, $source, 'getContent');
    return ExternalContentJsonApi::getJsonApi($endpoint, $query, $headers);
  }

  /**
   * Get URL query for querying content by taxonomy term.
   */
  public function getContentByTermQuery($source, array $term_ids, $limit = 1) {
    $plugin_config = $source->getPluginConfiguration();
    $term_field = $plugin_config['term_field'] ?? '';
    $term_value = implode(',', $term_ids);
    return [
      "filter[{$term_field}.drupal_internal__tid][value]" => $term_value,
      'include' => $this->getIncludes($source),
      'page[limit]' => $limit,
      'sort' => '-created',
    ];
  }

  /**
   * Get URL query for querying content by multiple taxonomy terms.
   */
  public function getContentByMultiTermQuery($source, array $term_ids, $limit = 1) {
    $plugin_config = $source->getPluginConfiguration();
    $term_field = $plugin_config['term_field'] ?? '';

    $query = [
      'include' => $this->getIncludes($source),
      'page[limit]' => $limit,
      'sort' => '-created',
    ];

    $groupname = "{$term_field}-group";
    $query[] = "&filter[{$groupname}][group][conjunction]=OR";

    foreach ($term_ids as $tid) {
      $query[] = "&filter[tid-{$tid}][condition][memberOf]={$groupname}";
      $query[] = "&filter[tid-{$tid}][condition][value]=" . $tid;
      $query[] = "&filter[tid-{$tid}][condition][path]={$term_field}.drupal_internal__tid";
    }

    return $query;
  }


}
