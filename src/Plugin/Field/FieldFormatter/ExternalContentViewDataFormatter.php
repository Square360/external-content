<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\external_content\Plugin\Field\FieldFormatter\ExternalContentFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'external_content_view_data' formatter.
 *
 * @FieldFormatter(
 *   id = "external_content_view_data",
 *   label = @Translation("View Stored Data"),
 *   description = @Translation("Displays the source, target ID, and title of the selected item without querying from the external source."),
 *   field_types = {
 *     "external_content_item"
 *   }
 * )
 */
class ExternalContentViewDataFormatter extends ExternalContentFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Remove the limit setting since we want to show all stored data
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Return empty form - no settings needed for viewing stored data
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->t('Shows all stored field data')];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $source_id = $item->source;
      $target_id = $item->target_id ?: $this->t('No target ID');
      $title = $item->title ?: $this->t('No title');

      // Get the source label from the external content source entity
      $source_label = $this->t('Unknown source');
      if ($source_id) {
        try {
          $source_entity = $this->entityTypeManager
            ->getStorage('external_content_source')
            ->load($source_id);
          if ($source_entity) {
            $source_label = $source_entity->getLabel();
          }
        } catch (\Exception $e) {
          // Fallback to source ID if entity can't be loaded
          $source_label = $source_id;
        }
      }

      // Format as "Title (target_id) @ Source Label"
      $formatted_value = $title . ' (' . $target_id . ') from ' . $source_label;

      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => '<div class="external-content-view-data">' . $formatted_value . '</div>',
      ];
    }

    return $elements;
  }

}
