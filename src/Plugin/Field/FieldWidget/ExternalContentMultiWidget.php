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

/**
 * Defines the 'external_content_multi' field widget.
 *
 * @FieldWidget(
 *   id = "external_content_multi",
 *   label = @Translation("External Content Multi"),
 *   field_types = {"external_content_item"},
 *   multiple_values = FALSE
 * )
 */
class ExternalContentMultiWidget extends WidgetBase {

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
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

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

    $title = $this->fieldDefinition->getLabel();
    $description = $this->getFilteredDescription();

    $elements = [];

    // Add AJAX wrapper for the entire field.
    $ajax_wrapper_id = implode('-', array_merge($parents, [$field_name])) . '-add-more-wrapper';

    $elements['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $elements['#suffix'] = '</div>';

    // Get default source from first item or first available source.
    $default_source = NULL;
    if (!empty($items[0]) && !empty($items[0]->source)) {
      $default_source = $items[0]->source;
    } else {
      $source_options = $this->getSourceOptions();
      $default_source = array_key_first($source_options);
    }

    // Add shared source selector.
    $elements['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Source'),
      '#description' => $this->t('Select a source for external content. This will apply to all items.'),
      '#options' => $this->getSourceOptions(),
      '#default_value' => $default_source,
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

    // Generate the form elements for the field's widget.
    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      $element = [
        '#title' => $is_multiple ? '' : $title,
        '#description' => $is_multiple ? '' : $description,
      ];
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        $elements[$delta] = $element;
      }
    }
    // Add 'add more' button for unlimited cardinality AFTER all field elements.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $elements['add_more'] = [
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

    $elements['#field_name'] = $field_name;
    $elements['#cardinality'] = $cardinality;
    $elements['#cardinality_multiple'] = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $elements['#required'] = $this->fieldDefinition->isRequired();
    $elements['#title'] = $title;
    $elements['#description'] = $description;
    $elements['#max_delta'] = $max;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Build a "label (target_id)" value that can be parsed for storage.
    $default_value = '';
    if (!empty($items[$delta]->target_id)) {
      $default_value = sprintf(
        '%s (%s)',
        $items[$delta]->title,
        $items[$delta]->target_id
      );
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

    // Final fallback to existing item source or first available source
    if (empty($current_source)) {
      $current_source = !empty($items[$delta]->source) ? $items[$delta]->source : array_key_first($this->getSourceOptions());
    }

    $route_params = ["source_id" => $current_source];
    $autocomplete_path = Url::fromRoute('external_content.autocomplete', $route_params);
    //$search["#attributes"]["data-autocomplete-path"] = $autocomplete_path->toString();

    $element['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item @num', ['@num' => $delta + 1]),
      '#description' => $this->t('Search for content from the selected source above.'),
      '#autocomplete_route_name' => 'external_content.autocomplete',
      '#autocomplete_route_parameters' => [
        'source_id' => $current_source,
      ],
      '#placeholder' => $this->t('Type to search'),
      '#default_value' => $default_value,
      '#cache' => ['max-age' => 0],
    ];

    $element['#element_validate'] = [
      [static::class, 'validate'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#field_parents' => $form['#parents'],
      // Only the first widget should be required.
      '#required' => $delta == 0 && $this->fieldDefinition->isRequired(),
      '#delta' => $delta,
      '#weight' => $delta,
    ];

    $element = $this->formElement($items, $delta, $element, $form, $form_state);

    if ($element) {
      // Allow modules to alter the field widget form element.
      $context = [
        'form' => $form,
        'widget' => $this,
        'items' => $items,
        'delta' => $delta,
        'default' => $this->isDefaultValueWidget($form_state),
      ];
      \Drupal::moduleHandler()->alter(['field_widget_single_element_form', 'field_widget_single_element_' . $this->getPluginId() . '_form'], $element, $form_state, $context);
    }

    return $element;
  }

  /**
   * AJAX callback to update all autocomplete fields when source changes.
   */
  public function updateAutocompleteForAll(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $field_name = $triggering_element['#array_parents'][count($triggering_element['#array_parents']) - 2];
    $field_element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));

    $source_id = $form_state->getValue($triggering_element['#parents']);
    // Update autocomplete route parameters for all search fields.
    foreach (array_keys($field_element) as $key) {
      if (is_numeric($key) && isset($field_element[$key]['search'])) {
        // Update the route parameters
        $field_element[$key]['search']['#autocomplete_route_parameters']['source_id'] = $source_id;

        // Generate the new autocomplete path and update the data attribute
        $route_params = ['source_id' => $source_id];
        $autocomplete_path = Url::fromRoute('external_content.autocomplete', $route_params);
        $field_element[$key]['search']['#attributes']['data-autocomplete-path'] = $autocomplete_path->toString();

        // Clear the field value since the source changed
        $field_element[$key]['search']['#value'] = '';
      }
    }

    return $field_element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Extract the source value before removing it
      $source = $values['source'] ?? null;

      // Remove the 'source' and 'add_more' values that are not field items.
      unset($values['source'], $values['add_more']);

      // The original delta, before drag-and-drop reordering, is needed to
      // route errors to the correct form element.
      foreach ($values as $delta => &$value) {
        if (is_array($value)) {
          $value['_original_delta'] = $delta;
        }
      }

      // Let the widget massage the submitted values, passing the source.
      $values = $this->massageFormValues($values, $form, $form_state, $source);

      // Assign the values and remove the empty ones.
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $item->_original_delta ?? $delta;
        unset($item->_original_delta, $item->_weight, $item->_actions);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * Validation callback for individual elements.
   */
  public static function validate($element, FormStateInterface $form_state) {
    if ($element['#parents'][0] == 'default_value_input') {
      return;
    }

    $search_value = $element['search']['#value'];
    if (empty($search_value)) {
      return;
    }

    $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($search_value);
    if (empty($id)) {
      $form_state->setError($element['search'], t('Invalid format. Item should be in the format "Title (ID)".'));
    }
  }

}
