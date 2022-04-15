<?php

namespace Drupal\external_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\external_content\ExternalContentJsonApi;

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
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value factory.
   * @param \Drupal\Core\Entity\EntityAutocompleteMatcher $entity_autocomplete_matcher
   *   The entity.autocomplete_matcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Manager.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, EntityAutocompleteMatcher $entity_autocomplete_matcher, EntityTypeManagerInterface $entity_type_manager) {
    $this->keyValueFactory = $key_value_factory;
    $this->entityAutocompleteMatcher = $entity_autocomplete_matcher;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('entity.autocomplete_matcher'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function handleNodeAutoComplete(Request $request) {

    $results = [];

    $source_id = $request->query->get('source_id');
    $input = $request->query->get('q');

    $storage = $this->entityTypeManager->getStorage('external_content_source');
    /** @var \Drupal\external_content\Entity\ExternalContentSource $source */
    $source = $storage->load($source_id);

    // Get the typed string from the URL, if it exists.
    if ($input) {

      $jsonapi = new ExternalContentJsonApi();
      $endpoint = $source->getResource();

      $endpoint = $source->getLookupResource();
      $query = $source->getLookupQuery($input);
      $json = $jsonapi->getJsonApi($endpoint, $query, TRUE)['data'];

      if ($json !== FALSE) {

        foreach ($json as $result) {
          $drupal_id = !empty($result['attributes']['drupal_internal__nid'])
            ? $result['attributes']['drupal_internal__nid']
            : $result['attributes']['drupal_internal__tid'];
          $title = !empty($result['attributes']['title'])
            ? $result['attributes']['title']
            : $result['attributes']['name'];
          $results[] = [
            'value' => "$title ($drupal_id)",
            'label' => "$title ($drupal_id)",
          ];
        }
      }
    }

    if (!$source->isTermResource()) {
      if (stripos('Most recent item', $input) !== FALSE) {
        $val = $this->t('Most recent item(s)'). " (-1)";
        array_unshift($results, [
          'value' => $val,
          'label' => $val,
        ]);
      }

    }

    return new JsonResponse($results);
  }

}
