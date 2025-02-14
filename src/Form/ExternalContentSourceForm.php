<?php

namespace Drupal\external_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * External Content Source form.
 *
 * @property \Drupal\external_content\ExternalContentSourceInterface $entity
 */
class ExternalContentSourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    /**
     * @var \Drupal\external_content\Entity\ExternalContentSource
     */
    $source = $this->entity;

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

    $form['term_resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Resource (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $source->getTermResource(),
      '#description' => $this->t("Resource from which to select filterable terms."),
      '#required' => FALSE,
    ];

    $form['term_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Field (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $source->getTermField(),
      '#description' => $this->t("Add a field name to determine which entity field on which to filter by term."),
      '#required' => FALSE,
    ];

    $form['includes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSONAPI Includes'),
      '#maxlength' => 255,
      '#default_value' => $source->getIncludes(),
      '#description' => $this->t(
        "JSONAPI 'includes' to request related data along with entity"
      ),
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
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
