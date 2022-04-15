<?php

namespace Drupal\external_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns list of available sources for this field as option list.
   *
   * @return array
   *   Option list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSourceOptions() {
    $options = [];
    $storage = $this->entityTypeManager->getStorage('external_content_source');
    $sources = $storage->loadMultiple();
    $enabled_sources = array_filter($this->fieldDefinition->getSetting('enabled_sources'));

    /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
    foreach ($sources as $source) {
      if (empty($enabled_sources) || in_array($source->getId(), $enabled_sources)) {
        $options[$source->getId()] = $source->getLabel();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $default_value = NULL;
    // Add AJAX wrapper.
    $ajax_id_parts = array_merge(
      $element["#field_parents"],
      [$items->getName(), $delta]
    );
    $ajax_wrapper_id = implode("-", $ajax_id_parts) . "-ajax";

    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    // Build a "label (target_id)' value that can be parsed for storage.
    if (!empty($items[$delta]->target_id)) {
      $default_value = sprintf(
        '%s (%d)',
        $items[$delta]->title,
        $items[$delta]->target_id
      );
    }

    $source_options = $this->getSourceOptions();

    $element['source'] = [
      '#type' => 'select',
      '#title' => $element['#title'],
      '#description' => $this->t('Select a source for external content.'),
      '#options' => $source_options,
      '#default_value' => $items[$delta]->source ?? NULL,
      '#ajax' => [
        'callback' => [$this, 'updateAutoCompleteSource'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => $ajax_wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating autocomplete...'),
        ],
      ],
    ];

    $default_source = $items[$delta]->source ?? array_key_first($source_options);

    $element['search'] = [
      '#description' => $this->t('Select item from external content.'),
      '#type' => 'textfield',
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#autocomplete_route_name' => 'external_content.autocomplete',
      '#autocomplete_route_parameters' => [
        'source_id' => $default_source,
      ],
      '#placeholder' => 'Type to search',
      '#default_value' => $default_value,
      '#cache' => ['max-age' => 0],
    ];

    return $element;
  }

  /**
   * AJAX Callback which updates autocomplete field on source selection.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array|mixed
   *   Returns updated form.
   */
  public function updateAutoCompleteSource(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    $source_id = $form_state->getValue($triggering_element['#parents']);
    $container = NestedArray::getValue(
      $form,
      array_slice($triggering_element['#array_parents'], 0, -1)
    );

    $search = $container["search"];

    $route_params = [
      "source_id" => $source_id,
    ];

    $autocomplete_path = Url::fromRoute('external_content.autocomplete', $route_params);
    $search["#attributes"]["data-autocomplete-path"] = $autocomplete_path->toString();
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
    if ($element["#parents"][0] == 'default_value_input') {
      return;
    }

    $value = $form_state->cleanValues()->getValue($field_name);
    if (empty($value[0]["search"])) {
      return;
    }

    $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value[0]["search"]);
    if (empty($id)) {
      $form_state->setValueForElement($element, '');
      return;
    }

  }

}
