<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
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
class ExternalContentPreviewFormatter extends ExternalContentFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $source_id = $item->source;
      $title = $item->title;
      $id = $item->target_id;

      $storage = $this->entityTypeManager->getStorage('external_content_source');
      /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
      $source = $storage->load($source_id);
      $label = $source->getLabel();
      $data = $source->getContent($id, $this->getSetting('limit'));

      // ToDo: make this a link again when we have a standard createLinkFromEntity method on the plugin..
//      $links = array_map(function ($item) {
//        return ExternalContentJsonApi::getLinkFromEntity($item);
//      }, $data['data']);

      $element[$delta] = [
        [
          '#markup' => "$title ($id) from $label",
        ],
//        [
//          '#theme' => 'item_list',
//          '#items' => $links,
//        ],
      ];
    }

    return $element;
  }

}
