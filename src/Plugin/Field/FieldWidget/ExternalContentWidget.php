<?php

namespace Drupal\external_content\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\som_api_integration_externalreference\ExternalSourceJsonApi;

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

  const acLimit = 5;
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

  protected function getSourceOptions() {
    $options = [];
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager =\Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entityTypeManager->getStorage('external_content');
    $sources = $storage->loadMultiple();
    /** @var \Drupal\external_content\Entity\ExternalContent $source */
    foreach ($sources as $source) {
      $options[$source->getId()] = $source->getLabel();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_value = NULL;
    // Add AJAX wrapper
    $ajax_id_parts = array_merge($element["#field_parents"], [$items->getName(), $delta]);
    $ajax_wrapper_id = implode("-",$ajax_id_parts) . "-ajax";

    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    // Build a "label (target_id)' value that can be parsed for storage.
    if (!empty($items[$delta]->target_id)) {
      $default_value = sprintf('%s (%d)', $items[$delta]->title, $items[$delta]->target_id);
    }

    // Disabled as we are using AJAX now.
//    $element['#attached']['library'][] = 'external_content/update-source';

    $element['source'] = [
      '#type' => 'select',
      '#title' => $this->t("Source"),
      '#options' => $this->getSourceOptions(),
      '#default_value' => isset($items[$delta]->source) ? $items[$delta]->source : NULL,
      '#ajax' => [
        'callback' => [$this, 'updateAutoCompleteSource'], //alternative notation
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => $ajax_wrapper_id, // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating autocomplete...'),
        ],
      ],
      '#attributes' => [
        'class' => ['external-content__source-selector']
      ]
    ];

    $element['search'] = [
      '#title' => $this->t('Search'),
      '#type' => 'textfield',
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#autocomplete_route_name' => 'external_content.autocomplete',
      '#autocomplete_route_parameters' => [
        'source_id' => 'insights_article',
      ],
      '#placeholder' => 'Type to search',
      '#default_value' => $default_value,
      '#cache' => ['max-age' => 0],
      '#attributes' => [
        'class' => ['external-content__search'],
      ],
    ];

    return $element;
  }

  public function updateAutoCompleteSource(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    $source_id = $form_state->getValue($triggering_element['#parents']);
    $container = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));

    $search = $container["search"];

    $route_params = [
      "source_id" => $source_id,
    ];

    $autocomplete_path = Url::fromRoute('external_content.autocomplete', $route_params);
    $search["#attributes"]["data-autocomplete-path"] = $autocomplete_path->toString();
    $search["#description"] = $autocomplete_path->toString();
    return $search;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $item = NULL;

    foreach ($values as $delta => &$item) {
      $item['delta'] = $delta;

      // Take "label (entity id)', match the ID from inside the parentheses.
      // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
      if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $item['search'], $matches)) {
        $item['title'] = trim($matches[1]);
        $item['target_id'] = trim($matches[2]);
        $item['uuid'] = trim($matches[2]);
      }
    }

    return $values;
  }


  /**
   * {@inheritdoc}
   */
  public static function validate($element, FormStateInterface $form_state) {

    $field_name = $element['#parents'][0];
    $value = $form_state->cleanValues()->getValue($field_name);

    $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value[0]["search"]);
    if (empty($id)) {
      $form_state->setValueForElement($element, '');
      return;
    }

//    $response =   public function getTermQuery($term_id, $limit=1) {getNodeByNid('insights_article', $id);
//
//    if ($response === FALSE) {
//      $form_state->setError($element, t('This article does not exist.'));
//    }
  }

}
