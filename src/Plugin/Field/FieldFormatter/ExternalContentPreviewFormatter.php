<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\external_content\ExternalContentJsonApi;

/**
 * Plugin implementation of the 'ExternalContentPreview' formatter.
 *
 * @FieldFormatter(
 *   id = "external_content_preview",
 *   label = @Translation("ExternalContentPreview"),
 *   field_types = {
 *     "external_content_item"
 *   }
 * )
 */
class ExternalContentPreviewFormatter extends FormatterBase {

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
    $element = [];

    foreach ($items as $delta => $item) {
      $source_id = $item->source;
      $title = $item->title;
      $id = $item->target_id;

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager =\Drupal::service('entity_type.manager');
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $entityTypeManager->getStorage('external_content_source');
      /** @var \Drupal\external_content\Entity\ExternalContent $source */
      $source = $storage->load($source_id);

      $label = $source->getLabel();


      $data = $source->getContentByTerm($id, $this->getSetting('limit'));

      $links = array_map(function($item) {
        return ExternalContentJsonApi::getLinkFromEntity($item);
      }, $data['data']);

      $element[$delta] = [
        [
          '#markup' =>  "$title ($id) from $label",
        ],
        [
          '#theme' => 'item_list',
          '#items' => $links
        ]
      ];
    }

    return $element;
  }


}
