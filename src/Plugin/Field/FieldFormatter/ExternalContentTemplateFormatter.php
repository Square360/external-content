<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\external_content\ExternalContentJsonApi;

/**
 * Plugin implementation of the 'ExternalContentTemplate' formatter.
 *
 * @FieldFormatter(
 *   id = "external_content_template",
 *   label = @Translation("ExternalContentTemplate"),
 *   field_types = {
 *     "external_content_item"
 *   }
 * )
 */
class ExternalContentTemplateFormatter extends FormatterBase {

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
      $id = $item->target_id;

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager =\Drupal::service('entity_type.manager');
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $entityTypeManager->getStorage('external_content');
      /** @var \Drupal\external_content\Entity\ExternalContent $source */
      $source = $storage->load($source_id);

      $data = $source->getContent($id, $this->getSetting('limit'));

      $render_children = [];
      foreach($data['data'] as $entity) {
        $render_children[] = [
          '#theme' => 'external_content',
          '#doc' => $entity,
          '#jsonapi' => $data,
          '#source_id' => $source_id,
          '#source' => $source
        ];
      }

      if (count($render_children) > 1) {
        $element[$delta] = [
            '#theme' => 'item_list',
            '#items' => $render_children
        ];
      }
      else {
        $element[$delta] = $render_children;
      }
    }

    return $element;
  }


}
