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
  id: 'jsonapi_title',
  label: new TranslatableMarkup('JSONAPI by Title'),
  description: new TranslatableMarkup('Selects JSONAPI entities by title.'),
)]
final class JsonApiTitle extends ExternalSourceTypePluginBase {

  function externalSourceConfigForm(array &$form_container, array &$plugin_configuration) {

    $includes = $plugin_configuration['includes'] = $plugin_configuration['includes'] ?? '';

    $form_container['includes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSONAPI Includes'),
      '#default_value' => $includes,
      '#description' => $this->t(
        "JSONAPI 'includes' parameter which will be sent with each request."
      ),
      '#required' => FALSE,
    ];

  }

}
