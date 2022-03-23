<?php

namespace Drupal\external_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\external_content\ExternalContentJsonApi;
use Drupal\som_api_integration_externalreference\ExternalSourceJsonApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
  public function handleNodeAutoComplete(Request $request) {

    $results = [];

    $source_id = $request->query->get('source_id');
    $input = $request->query->get('q');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager =\Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $entityTypeManager->getStorage('external_content');
    /** @var \Drupal\external_content\Entity\ExternalContent $source */
    $source = $storage->load($source_id);


    // Get the typed string from the URL, if it exists.
    if ($input) {

      $external_source = new ExternalContentJsonApi();
      $endpoint = $source->getResource();

      $endpoint = $source->getLookupResource();
      $query = $source->getLookupQuery($input);
      $json = $external_source->getJsonApi($endpoint, $query, TRUE)['data'];

      if ($json !== FALSE) {

        $nid = 'drupal_internal__nid';
        $label_id = 'title';
        foreach ($json as $result) {
          $uuid = $result['id'];
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

    return new JsonResponse($results);
  }

}
