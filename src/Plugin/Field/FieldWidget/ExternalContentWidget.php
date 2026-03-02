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

    // Pass the widget setting to the element for validation
    $element['#allow_multiple_values'] = $this->getSetting('allow_multiple_values');

    // Build the "label (target_id)' values
    if (!empty($items[$delta]->target_id)) {
      // Handle multiple values stored as comma-separated strings
      // Always show all values regardless of allow_multiple_values setting
      $target_ids = array_map('trim', explode(',', $items[$delta]->target_id));
      $titles = array_map('trim', explode(',', $items[$delta]->title));

      $formatted_values = [];
      foreach ($target_ids as $index => $target_id) {
        if (isset($titles[$index])) {
          $formatted_values[] = sprintf('%s (%s)', $titles[$index], $target_id);
        }
      }

      $default_value = implode(', ', $formatted_values);
    }

    $source_options = $this->getSourceOptions();
    $has_multiple_sources = count($source_options) > 1;

    $element['source'] = [
      '#type' => 'select',
      '#title' => $element['#title'],
      '#description' => $this->t(
        $has_multiple_sources ? 'Select a source for external content.'
        : ''
      ),

      '#options' => $source_options,
      '#default_value' => $items[$delta]->source ?? array_key_first($source_options),
      '#disabled' => !$has_multiple_sources, // Disable when only one source
    ];

    // Only add AJAX if there are multiple sources
    if ($has_multiple_sources) {
      $element['source']['#ajax'] = [
        'callback' => [$this, 'updateAutoCompleteSource'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => $ajax_wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating autocomplete...'),
        ],
      ];
    }

    $default_source = $items[$delta]->source ?? array_key_first($source_options);

    // Get field description from configuration, or use default descriptions
    $field_description = $this->fieldDefinition->getDescription();
    $search_description = !empty($field_description)
      ? $field_description
      : ($this->getSetting('allow_multiple_values')
          ? $this->t("Select an item from external content. For multiple values, you can separate with commas.")
          : $this->t("Select a single item from external content. Multiple values will be ignored"));

    $element['search'] = [
      '#title' => NULL, // No title on search field since source field has the title
      '#description' => $search_description,
      '#type' => 'textfield',
      '#maxlength' => 2048,
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

    // Add quantity field if enabled.
    if ($this->getSetting('enable_quantity')) {
      $max_quantity = $this->getSetting('max_quantity');

      $element['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#description' => $max_quantity > 0
          ? $this->t('Number of items to fetch (max: @max)', ['@max' => $max_quantity])
          : $this->t('Number of items to fetch'),
        '#default_value' => $items[$delta]->quantity ?? 1,
        '#min' => 1,
        '#step' => 1,
        '#required' => FALSE,
      ];

      if ($max_quantity > 0) {
        $element['quantity']['#max'] = $max_quantity;
      }
    }

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

    // Clear value.
    $search["#value"] = '';

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
    $allow_multiple = $this->getSetting('allow_multiple_values');

    foreach ($values as $delta => &$item) {
      $item['delta'] = $delta;

      if ($allow_multiple && !empty($item['search'])) {
        // Handle multiple values separated by commas
        $search_values = array_map('trim', explode(',', $item['search']));

        $titles = [];
        $target_ids = [];

        foreach ($search_values as $search_value) {
          if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $search_value, $matches)) {
            $titles[] = trim($matches[1]);
            $target_ids[] = trim($matches[2]);
          }
        }

        // Store as comma-separated strings
        $item['title'] = implode(', ', $titles);
        $item['target_id'] = implode(', ', $target_ids);
      }
      else {
        // Single value processing (original logic)
        // Take "label (entity id)', match the ID from inside the parentheses.
        // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
        if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $item['search'], $matches)) {
          $item['title'] = trim($matches[1]);
          $item['target_id'] = trim($matches[2]);
        }
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

    // Get the widget setting from the element
    $allow_multiple = $element['#allow_multiple_values'] ?? FALSE;

    $search_input = $value[0]["search"];

    if ($allow_multiple) {
      // Validate multiple comma-separated values
      $search_values = array_map('trim', explode(',', $search_input));
      $invalid_values = [];

      foreach ($search_values as $search_value) {
        if (!empty($search_value)) {
          $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($search_value);
          if (empty($id)) {
            $invalid_values[] = $search_value;
          }
        }
      }

      if (!empty($invalid_values)) {
        $form_state->setError($element['search'], t('The following values are not in the correct format: @values. Use the format "label (id)" for each value.', [
          '@values' => implode(', ', $invalid_values)
        ]));
        return;
      }
    }
    else {
      // Single value validation (original logic)
      $id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($search_input);
      if (empty($id)) {
        $form_state->setError($element['search'], t('The value is not in the correct format. Use the format "label (id)".'));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allow_multiple_values' => FALSE,
      'enable_quantity' => FALSE,
      'max_quantity' => 10,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['allow_multiple_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#default_value' => $this->getSetting('allow_multiple_values'),
      '#description' => $this->t('When enabled, the widget will accept multiple values separated by commas in the format: "label (id), label (id), ..."'),
    ];

    $elements['enable_quantity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable quantity selection'),
      '#default_value' => $this->getSetting('enable_quantity'),
      '#description' => $this->t('Allow content authors to specify how many items to fetch from the external source.'),
    ];

    $elements['max_quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum quantity'),
      '#default_value' => $this->getSetting('max_quantity'),
      '#min' => 0,
      '#description' => $this->t('Maximum number of items authors can request. Set to 0 for unlimited.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][enable_quantity]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('allow_multiple_values')) {
      $summary[] = $this->t('Multiple values allowed');
    }
    else {
      $summary[] = $this->t('Single value only');
    }

    if ($this->getSetting('enable_quantity')) {
      $max = $this->getSetting('max_quantity');
      $summary[] = $this->t('Quantity enabled (max: @max)', [
        '@max' => $max == 0 ? $this->t('unlimited') : $max,
      ]);
    }

    return $summary;
  }

}
