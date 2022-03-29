<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

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
class ExternalContentTemplateFormatter extends ExternalContentFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $source_id = $item->source;
      $id = $item->target_id;

      $storage = $this->entityTypeManager->getStorage('external_content_source');
      /** @var \Drupal\external_content\Entity\ExternalContent $source */
      $source = $storage->load($source_id);

      $data = $source->getContent($id, $this->getSetting('limit'));

      $render_children = [];
      foreach ($data['data'] as $entity) {
        $render_children[] = [
          '#theme' => 'external_content',
          '#doc' => $entity,
          '#jsonapi' => $data,
          '#source_id' => $source_id,
          '#source' => $source,
        ];
      }

      if (count($render_children) > 1) {
        $element[$delta] = [
          '#theme' => 'item_list',
          '#items' => $render_children,
        ];
      }
      else {
        $element[$delta] = $render_children;
      }
    }

    return $element;
  }

}
