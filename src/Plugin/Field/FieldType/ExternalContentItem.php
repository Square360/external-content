<?php

namespace Drupal\external_content\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'external_content_item' field type.
 *
 * @FieldType(
 *   id = "external_content_item",
 *   label = @Translation("External Content"),
 *   category = @Translation("Reference"),
 *   description = @Translation("A field that references content from an external source."),
 *   default_widget = "external_content_default",
 *   default_formatter = "external_content_preview"
 * )
 *
 * @DCG
 * If you are implementing a single value field type you may want to inherit
 * this class form some of the field type classes provided by Drupal core.
 * Check out /core/lib/Drupal/Core/Field/Plugin/Field/FieldType directory for a
 * list of available field type implementations.
 */
class ExternalContentItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'enabled_sources' => [],
    ];
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $enabled_sources = $this->getSetting('enabled_sources');

    $element['enabled_sources'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t("Enabled Sources"),
      '#options' => $this->getSourceOptions(),
      '#default_value' => empty($enabled_sources) ? [] : $enabled_sources,
      '#description' => $this->t('If no sources are selected, all sources will be available for selection.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('target_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['target_id'] = DataDefinition::create('string')
      ->setLabel(t('Target ID'))
      ->setRequired(TRUE);
    $properties['source'] = DataDefinition::create('string')
      ->setLabel(t('Source'))
      ->setRequired(TRUE);
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title of target at time of creation.'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {

    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();

    // No length constraints needed for TEXT fields
    // Removed target_id and title length constraints since they are now TEXT fields

    $constraints[] = $constraint_manager->create('ComplexData', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'target_id' => [
        'type' => 'text',
        'size' => 'normal',
        'not null' => FALSE,
        'description' => 'ID of selected target',
      ],
      'source' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'Source of selection.',
      ],
      'title' => [
        'type' => 'text',
        'size' => 'normal',
        'not null' => FALSE,
        'description' => 'Label of target item.',
      ],
    ];

    $schema = [
      'columns' => $columns,
      // @DCG Add indexes here if necessary.
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, 50));
    return $values;
  }

  /**
   * Returns list of available sources as option list.
   *
   * @return array
   *   Options list.
   */
  protected function getSourceOptions() {
    $options = [];
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $storage = $entityTypeManager->getStorage('external_content_source');
    $sources = $storage->loadMultiple();
    /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
    foreach ($sources as $source) {
      $options[$source->getId()] = $source->getLabel();
    }
    return $options;
  }

}
