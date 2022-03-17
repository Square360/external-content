<?php

namespace Drupal\external_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'external_content_default' field widget.
 *
 * @FieldWidget(
 *   id = "external_content_default",
 *   label = @Translation("ExternalContentDefault"),
 *   field_types = {"external_content_item"},
 * )
 */
class ExternalContentWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'foo' => 'bar',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Some setting (Foo)'),
      '#default_value' => $this->getSetting('foo'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Foo: @foo', ['@foo' => $this->getSetting('foo')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_value = NULL;

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager =\Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entityTypeManager->getStorage('external_content');
    $sources = $storage->loadMultiple();

    $options = [];
    /** @var \Drupal\external_content\Entity\ExternalContent $source */
    foreach ($sources as $source) {
      $options[$source->getId()] = $source->getLabel();
    }

    // Build a "label (nid)' value that can be parse for storage.
    if (!empty($items[$delta]->iid)) {
      $default_value = sprintf('%s (%d)', $items[$delta]->title_search, $items[$delta]->nid);
    }

    // Create container copying parent element items
    $element['container'] = $element + [
        '#type' => 'fieldset',
    ];

    $element['container']['source'] = [
      '#type' => 'select',
      '#title' => $this->t("Source"),
      '#options' => $options,
      '#default_value' => isset($items[$delta]->source) ? $items[$delta]->source : NULL,
    ];

    $element['container']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Search"),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
    ];

    return $element;
  }

}
