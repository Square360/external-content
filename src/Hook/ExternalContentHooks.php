<?php

declare(strict_types=1);

namespace Drupal\external_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for External Content module.
 */
final class ExternalContentHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'external_content' => [
        'variables' => [
          'doc' => NULL,
          'field' => NULL,
          'link' => NULL,
          'response' => NULL,
          'source_id' => NULL,
          'source' => NULL,
        ],
        'template' => 'external-content',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_external_content')]
  public function themeSuggestionsExternalContent(array $variables): array {
    /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
    $source = $variables['source'];
    $suggestions = [
      'external_content',
      'external_content__' . $source->id(),
      'external_content__' . $source->getType(),
      'external_content__' . $variables['field'],
    ];
    return $suggestions;
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_external_content')]
  public function preprocessExternalContent(array &$variables): void {
    /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
    $source = $variables['source'];
    $plugin_id = $source->getType();
    $classes = [
      'external-content',
      'external-content--' . $source->id(),
      'external-content--' . $plugin_id,
    ];

    if (($variables['attributes'] ?? NULL) instanceof \Drupal\Core\Template\Attribute) {
      $variables['attributes']->addClass($classes);
      return;
    }

    $variables['attributes']['class'] = array_merge(
      (array) ($variables['attributes']['class'] ?? []),
      $classes,
    );
  }

}
