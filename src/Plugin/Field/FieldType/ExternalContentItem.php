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
    $value = $this->get('target_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    // @DCG
    // See /core/lib/Drupal/Core/TypedData/Plugin/DataType directory for
    // available data types.
    $properties['target_id'] = DataDefinition::create('string')
      ->setLabel(t('Target ID'))
      ->setRequired(TRUE);
    $properties['uuid'] = DataDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setRequired(FALSE);
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

    $options['target_id']['Length']['max'] = 32;
    $options['uuid']['Length']['max'] = 32;
    $options['title']['Length']['max'] = 255;

    $constraints[] = $constraint_manager->create('ComplexData', $options);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'target_id' => [
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
      'title' => [
        'type' => 'varchar',
        'length' => 255,
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

}
