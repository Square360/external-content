<?php

namespace Drupal\external_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * External Content Source form.
 *
 * @property \Drupal\external_content\ExternalContentSourceInterface $entity
 */
class ExternalContentSourceForm extends EntityForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    /**
     * @var \Drupal\external_content\Entity\ExternalContentSource
     */
    $source = $this->entity;

    // Get available external source types from the plugin manager.
    $sourceTypePluginManager = \Drupal::service('plugin.manager.external_source_type');
    $plugin_definitions = $sourceTypePluginManager->getDefinitions();
    $type_options = [];
    foreach ($plugin_definitions as $plugin_id => $definition) {
      $type_options[$plugin_id] = $definition['label'];
    }

//    xdebug_break();

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $type_options,
      '#default_value' => $source->getType(),
      '#description' => $this->t('Select the external source type.'),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a type -'),
      '#ajax' => [
        'callback' => '::typeConfigCallback',
        'wrapper' => 'type-config-container',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];


    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $source->getLabel(),
      '#description' => $this->t("Label for the ExternalContent."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $source->getId(),
      '#description' => $this->t("Label for the ExternalContent."),
      '#machine_name' => [
        'exists' => ['\Drupal\external_content\Entity\ExternalContentSource', 'load'],
      ],
      '#disabled' => !$source->isNew(),
    ];

    $form['resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource'),
      '#maxlength' => 255,
      '#default_value' => $source->getResource(),
      '#description' => $this->t("JSONAPI Resource Endpoint."),
      '#required' => TRUE,
    ];

    $form['cache_timeout'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Cache timeout'),
      '#maxlength' => 255,
      '#default_value' => $source->getCacheTimeout(),
      '#description' => $this->t(
        "Length of time, in seconds, for which we should cache results from this source. "
      ),
      '#required' => TRUE,
    ];

    // Type-specific configuration container
    $form['type_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source Specific Configuration'),
      '#prefix' => '<div id="type-config-container">',
      '#suffix' => '</div>',
      '#description' => $this->t('Configuration options for the selected source type.'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['!value' => ''],
        ],
      ],
    ];

    // Load type-specific configuration
    $selected_type = $form_state->getValue('type') ?: $source->getType();
    if ($selected_type && isset($type_options[$selected_type])) {
      try {
        $plugin = $sourceTypePluginManager->createInstance($selected_type);

        // Get existing plugin configuration from the entity
        $plugin_configuration = $source->getPluginConfiguration() ?: [];

        // Let the plugin modify the form
        $plugin->externalSourceConfigForm($form['type_config'], $plugin_configuration);
      } catch (\Exception $e) {
        $form['type_config']['error'] = [
          '#markup' => $this->t('Error loading configuration for type: @type', ['@type' => $selected_type]),
        ];
      }
    }

    return $form;
  }

  /**
   * AJAX callback for type configuration.
   */
  public function typeConfigCallback(array &$form, FormStateInterface $form_state) {
    return $form['type_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save plugin configuration if a type is selected
    $selected_type = $form_state->getValue('type');
    if ($selected_type) {
      $plugin_manager = \Drupal::service('plugin.manager.external_source_type');
      try {

        // Extract plugin configuration from form values
        $plugin_configuration = [];
        if (isset($form['type_config']) && is_array($form['type_config'])) {
          foreach ($form['type_config'] as $key => $element) {
            if (strpos($key, '#') !== 0 && $form_state->hasValue($key)) {
              $plugin_configuration[$key] = $form_state->getValue($key);
            }
          }
        }

        // Store plugin configuration in the entity
        $this->entity->set('plugin_configuration', $plugin_configuration);
      } catch (\Exception $e) {
        $this->messenger()->addError($this->t('Error saving plugin configuration: @error', ['@error' => $e->getMessage()]));
      }
    }

    $result = parent::save($form, $form_state);
    $message_args = [
      '%label' => $this->entity->label(),
      '%resource' => $this->entity->getResource(),
    ];
    $message = $result == SAVED_NEW
      ? $this->t('Created new External Content Source %label (%resource).', $message_args)
      : $this->t('Updated External Content Source %label (%resource).', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
