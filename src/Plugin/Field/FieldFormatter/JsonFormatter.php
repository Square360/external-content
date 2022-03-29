<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'externalreference_field' formatter.
 *
 * @FieldFormatter(
 *   id = "external_content_json",
 *   label = @Translation("JSON output (for testing)"),
 *   field_types = {
 *     "external_content_item"
 *   }
 * )
 */
class JsonFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'limit' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number results to show.'),
      '#min' => 1,
      '#default_value' => $this->getSetting('limit'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Max @limit items', ['@limit' => $this->getSetting('limit')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  private function viewValue(FieldItemInterface $item) {

    $source_id = $item->source;
    $id = $item->target_id;

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entityTypeManager->getStorage('external_content_source');
    /** @var \Drupal\external_content\Entity\ExternalContent $source */
    $source = $storage->load($source_id);

    $data = $source->getContentByTerm($id, $this->getSetting('limit'));

    return [
      '#type' => 'html_tag',
      '#tag' => 'pre',
      'child' => [
        '#type' => 'html_tag',
        '#tag' => 'code',
        'child' => [
          '#plain_text' => json_encode($data),
        ],
      ],
    ];
  }

}
