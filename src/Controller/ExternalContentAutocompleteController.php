<?php

namespace Drupal\external_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for External Content routes.
 */
class ExternalContentAutocompleteController extends ControllerBase {

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * The entity.autocomplete_matcher service.
   *
   * @var \Drupal\Core\Entity\EntityAutocompleteMatcher
   */
  protected $entityAutocompleteMatcher;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $entity_autocomplete_matcher
   *   The entity.autocomplete_matcher service.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, EntityAutocompleteMatcher $entity_autocomplete_matcher) {
    $this->keyValueFactory = $key_value_factory;
    $this->entityAutocompleteMatcher = $entity_autocomplete_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('entity.autocomplete_matcher')
    );
  }

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
