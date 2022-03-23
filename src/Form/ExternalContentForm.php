<?php

namespace Drupal\external_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the ExternalContent add and edit forms.
 */
class ExternalContentForm extends EntityForm {

  /**
   * Constructs an ExternalContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /**
     * @var \Drupal\external_content\Entity\ExternalContent
     */
    $external_content = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $external_content->getLabel(),
      '#description' => $this->t("Label for the ExternalContent."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $external_content->getId(),
      '#description' => $this->t("Label for the ExternalContent."),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
//      '#disabled' => !$external_content->isNew(),
    ];

    $form['resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource'),
      '#maxlength' => 255,
      '#default_value' => $external_content->getResource(),
      '#description' => $this->t("JSONAPI Resource Endpoint."),
      '#required' => TRUE,
    ];

    $form['term_resource'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Resource (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $external_content->getTermResource(),
      '#description' => $this->t("Resource from which to select filterable terms."),
      '#required' => FALSE,
    ];

    $form['term_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Field (Optional)'),
      '#maxlength' => 255,
      '#default_value' => $external_content->getTermField(),
      '#description' => $this->t("Add a field name to determine which entity field on which to filter by term."),
      '#required' => FALSE,
    ];

    $form['includes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSONAPI Includes'),
      '#maxlength' => 255,
      '#default_value' => $external_content->getIncludes(),
      '#description' => $this->t("JSONAPI 'includes' string to request related data from "),
      '#required' => TRUE,
    ];

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $external_content = $this->entity;
    $status = $external_content->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label ExternalContent created.', [
        '%label' => $external_content->getLabel(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label ExternalContent updated.', [
        '%label' => $external_content->getLabel(),
      ]));
    }

    $form_state->setRedirect('entity.external_content.collection');
  }

  /**
   * Helper function to check whether an ExternalContent configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('external_content')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}