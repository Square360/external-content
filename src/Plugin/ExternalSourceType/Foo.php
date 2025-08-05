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
  id: 'foo',
  label: new TranslatableMarkup('Foo'),
  description: new TranslatableMarkup('Foo description.'),
)]
final class Foo extends ExternalSourceTypePluginBase {

  function externalSourceConfigForm(array &$form_container, array &$plugin_configuration) {
    // This method can be used to add configuration options for the Foo source type.
    $form_container['foo_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo Setting'),
      '#default_value' => $plugin_configuration['foo_setting'] ?? '',
      '#description' => $this->t('A setting specific to the Foo external source type.'),
      '#required' => FALSE,
    ];
  }
}
