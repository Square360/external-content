<?php

namespace Drupal\external_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for External Content Formatters.
 */
abstract class ExternalContentFormatterBase extends FormatterBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

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
   * Get the effective limit for fetching content.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return int
   *   The limit to use when fetching content.
   */
  protected function getEffectiveLimit($item) {
    $formatter_limit = $this->getSetting('limit');
    $item_quantity = $item->quantity;

    // If quantity is not set, use formatter limit.
    if ($item_quantity === NULL) {
      return $formatter_limit;
    }

    // Use the minimum of quantity and formatter limit.
    // This ensures formatter limit acts as a cap.
    return min($item_quantity, $formatter_limit);
  }

}
