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
 *   label = @Translation("ExternalContentItem"),
 *   category = @Translation("General"),
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
    $settings = ['bar' => 'beer'];
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element['bar'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bar'),
      '#default_value' => $this->getSetting('bar'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uuid')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    // @DCG
    // See /core/lib/Drupal/Core/TypedData/Plugin/DataType directory for
    // available data types.
    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('id'))
      ->setRequired(TRUE);
    $properties['UUID'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(TRUE);
    $properties['source'] = DataDefinition::create('string')
      ->setLabel(t('Source'))
      ->setRequired(TRUE);
    $properties['summary'] = DataDefinition::create('string')
      ->setLabel(t('Summary of target'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();

    // @DCG Suppose our value must not be longer than 10 characters.
    $options['value']['Length']['max'] = 10;

    // @DCG
    // See /core/lib/Drupal/Core/Validation/Plugin/Validation/Constraint
    // directory for available constraints.
    $constraints[] = $constraint_manager->create('ComplexData', $options);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'id' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'ID of selected target',
      ],
      'uuid' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'UUID of selected target',
      ],
      'source' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'Source of selection.',
      ],
      'summary' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
        'description' => 'Column description.',
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

}
