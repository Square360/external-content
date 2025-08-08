<?php

namespace Drupal\external_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Defines the 'external_content_group' field widget.
 *
 * @FieldWidget(
 *   id = "external_content_group",
 *   label = @Translation("External Content Group"),
 *   field_types = {"external_content_item"},
 *   multiple_values = TRUE
 * )
 */
class ExternalContentGroupWidget extends WidgetBase {

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
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $element['#field_parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    // Add AJAX wrapper for the entire field.
    $ajax_wrapper_id = Html::getUniqueId(implode('-', array_merge($parents, [$field_name])) . '-add-more-wrapper');

    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Get default source from first item or first available source.
    $default_source = NULL;
    if (!empty($items[0]) && !empty($items[0]->source)) {
      $default_source = $items[0]->source;
    } else {
      $source_options = $this->getSourceOptions();
      $default_source = array_key_first($source_options);
    }

    // Get current source value - try multiple approaches to get the current source
    $current_source = null;

    // First, try to get from user input (during AJAX rebuilds)
    $user_input = $form_state->getUserInput();
    if (!empty($user_input)) {
      $source_input_path = array_merge($parents, [$field_name, 'source']);
      $current_source = NestedArray::getValue($user_input, $source_input_path);
    }

    // Fallback to form state values
    if (empty($current_source)) {
      $form_state_values = $form_state->getValues();
      $current_source = NestedArray::getValue($form_state_values, array_merge($parents, [$field_name, 'source']));
    }

    // Final fallback to default source
    if (empty($current_source)) {
      $current_source = $default_source;
    }

    // Add shared source selector.
    $element['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Source'),
      '#description' => $this->t('Select a source for external content. This will apply to all items.'),
      '#options' => $this->getSourceOptions(),
      '#default_value' => $current_source,
      '#ajax' => [
        'callback' => [$this, 'updateAutocompleteForAll'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => $ajax_wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating autocomplete...'),
        ],
      ],
      '#weight' => -10,
    ];

    // Generate the form elements for each search field.
    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // Build a "label (target_id)" value that can be parsed for storage.
      $default_value = '';
      if (!empty($items[$delta]->target_id)) {
        $default_value = sprintf(
          '%s (%s)',
          $items[$delta]->title,
          $items[$delta]->target_id
        );
      }

      $element[$delta] = [
        '#type' => 'textfield',
        '#title' => $this->t('Item @num', ['@num' => $delta + 1]),
        '#description' => $this->t("Select item from external content. For standard sources you can also select 'Most recent item(s)' instead of searching by title."),
        '#autocomplete_route_name' => 'external_content.autocomplete',
        '#autocomplete_route_parameters' => [
          'source_id' => $current_source,
        ],
        '#placeholder' => $this->t('Type to search'),
        '#default_value' => $default_value,
        '#cache' => ['max-age' => 0],
        '#weight' => $delta,
      ];
    }

    // Add 'add more' button for unlimited cardinality.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $element['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($field_name, '-', '_') . '_add_more',
        '#value' => $this->t('Add another item'),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [array_merge($parents, [$field_name])],
        '#submit' => [[get_class($this), 'addMoreSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'addMoreAjax'],
          'wrapper' => $ajax_wrapper_id,
          'effect' => 'fade',
        ],
        '#weight' => 1000, // Ensure it appears at the bottom
      ];
    }

    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    return $element;
  }

  /**
   * AJAX callback to update all autocomplete fields when source changes.
   */
  public function updateAutocompleteForAll(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Navigate back to the field element
    $field_element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));

    $source_id = $form_state->getValue($triggering_element['#parents']);

    // Update autocomplete route parameters for all search fields.
    foreach (array_keys($field_element) as $key) {
      if (is_numeric($key)) {
        // Update the route parameters
        $field_element[$key]['#autocomplete_route_parameters']['source_id'] = $source_id;

        // Generate the new autocomplete path and update the data attribute
        $route_params = ['source_id' => $source_id];
        $autocomplete_path = Url::fromRoute('external_content.autocomplete', $route_params);
        $field_element[$key]['#attributes']['data-autocomplete-path'] = $autocomplete_path->toString();

        // Clear the field value since the source changed
        $field_element[$key]['#value'] = '';
        $field_element[$key]['#default_value'] = '';
      }
    }

    return $field_element;
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Navigate to the widget element
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#parents'][count($element['#parents']) - 1];
    $parents = array_slice($element['#parents'], 0, -1);

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Navigate back to the field element
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $massaged_values = [];
    $source = $values['source'] ?? null;

    foreach ($values as $delta => $value) {
      // Skip non-numeric keys (like 'source', 'add_more') and empty search values.
      if (!is_numeric($delta) || empty($value)) {
        continue;
      }

      // Take "label (entity id)", match the ID from inside the parentheses.
      // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
      if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $value, $matches)) {
        $massaged_values[] = [
          'source' => $source,
          'title' => trim($matches[1]),
          'target_id' => trim($matches[2]),
          'uuid' => trim($matches[2]),
        ];
      }
    }

    return $massaged_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function validate($element, FormStateInterface $form_state) {
    if ($element['#parents'][0] == 'default_value_input') {
      return;
    }

    // Get all the field values
    $values = $element['#value'];

    foreach ($values as $delta => $value) {
      // Skip non-numeric keys and empty values
      if (!is_numeric($delta) || empty($value)) {
        continue;
      }

      $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value);
      if (empty($id)) {
        $form_state->setError($element[$delta], t('Invalid format for Item @num. Should be in the format "Title (ID)".', [
          '@num' => $delta + 1
        ]));
      }
    }
  }

}
