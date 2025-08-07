<?php

declare(strict_types=1);

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_content\Attribute\ExternalSourceType;
use Drupal\external_content\ExternalSourceTypePluginBase;
use Drupal\Core\Link;

/**
 * Plugin implementation of the external_source_type.
 */
#[ExternalSourceType(
  id: 'foo',
  label: new TranslatableMarkup('Foo'),
  description: new TranslatableMarkup('Foo description.'),
)]
final class Foo extends ExternalSourceTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete($source, string $input): array {
    // Dummy implementation - returns empty array
    // In a real implementation, this would search the external source
    // and return matching results based on the input string
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContent($source, $id, int $limit = 1) {
    // Dummy implementation - returns false to indicate no content found
    // In a real implementation, this would fetch content from the external source
    // based on the provided ID and limit
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($originalData): array {
    // Dummy implementation - returns empty array
    // In a real implementation, this would parse the response data structure
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkToEntity($doc): Link {
    // Dummy implementation - extract title from doc and return as Link without URL
    $title = $doc['title'] ?? $doc['name'] ?? 'Untitled';
    return Link::createFromRoute($title, '<none>');
  }

  /**
   * {@inheritdoc}
   */
  public function externalSourceConfigForm(array &$form_container, array &$plugin_configuration) {
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
