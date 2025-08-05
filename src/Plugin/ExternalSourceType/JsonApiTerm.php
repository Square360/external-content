<?php

declare(strict_types=1);

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_content\Attribute\ExternalSourceType;
use Drupal\external_content\ExternalSourceTypePluginBase;

/**
 * Plugin implementation of the external_source_type.
 */
#[ExternalSourceType(
  id: 'jsonapi_term',
  label: new TranslatableMarkup('JSONAPI by Term'),
  description: new TranslatableMarkup('Selects JSONAPI entities by taxonomy term.'),
)]
final class JsonApiTerm extends ExternalSourceTypePluginBase {

  function externalSourceConfigForm(array &$form_container, array &$plugin_configuration): array {

    $includes = $plugin_configuration['includes'] ?? '';
    $term_resource = $plugin_configuration['term_resource'] ?? '';
    $term_field = $plugin_configuration['term_field'] ?? '';

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

}
