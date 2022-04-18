<?php

namespace Drupal\external_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_content\ExternalContentJsonApi;
use Drupal\external_content\ExternalContentSourceInterface;

/**
 * Defines the external_content_source entity type.
 *
 * @ConfigEntityType(
 *   id = "external_content_source",
 *   label = @Translation("External Content Source"),
 *   label_collection = @Translation("External Content Sources"),
 *   label_singular = @Translation("External Content Source"),
 *   label_plural = @Translation("External Content Sources"),
 *   label_count = @PluralTranslation(
 *     singular = "@count External Content Source",
 *     plural = "@count External Content Sources",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\external_content\ExternalContentSourceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\external_content\Form\ExternalContentSourceForm",
 *       "edit" = "Drupal\external_content\Form\ExternalContentSourceForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "external_content_source",
 *   admin_permission = "administer external_content_source",
 *   links = {
 *     "collection" = "/admin/structure/external-content-source",
 *     "add-form" = "/admin/structure/external-content-source/add",
 *     "edit-form" =
 *   "/admin/structure/external-content-source/{external_content_source}",
 *     "delete-form" =
 *   "/admin/structure/external-content-source/{external_content_source}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "resource",
 *     "includes",
 *     "term_resource",
 *     "term_field",
 *   }
 * )
 */
class ExternalContentSource extends ConfigEntityBase implements ExternalContentSourceInterface {

  const LOOKUP_LIMIT = 5;

  const CACHE_TIMEOUT = (60 * 60);

  /**
   * The ExternalContentSource ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ExternalContentSource label.
   *
   * @var string
   */
  protected $label;

  /**
   * JSONAPI Resource endpoint.
   *
   * @var string
   */
  protected $resource;

  /**
   * Optional term bundle to extract list of terms.
   *
   * @var string
   */
  protected $term_resource;

  /**
   * Optional term if this source should be filtered by term.
   *
   * @var string
   */
  protected $term_field;

  /**
   * Optional list of includes to request related data from JSONAPI.
   *
   * @var string
   */
  protected $includes;

  /**
   * Returns ID.
   *
   * @return int|string|null
   *   ID.
   */
  public function getId() {
    return $this->id();
  }

  /**
   * Returns label.
   *
   * @return string
   *   Label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Returns term resource.
   *
   * @return string
   *   Term resource.
   */
  public function getTermResource() {
    return $this->term_resource;
  }

  /**
   * Returns term field.
   *
   * @return string
   *   Term field name.
   */
  public function getTermField() {
    return $this->term_field;
  }

  /**
   * Returns string of JSONAPI includes.
   *
   * @return string
   *   JSONAPI include string.
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * Returns resource.
   *
   * @return string
   *   Resource.
   */
  public function getResource() {
    return $this->resource;
  }

  /**
   * Returns whether this resource is a simple node resource or node by term.
   *
   * @return bool
   *   True if term resource.
   */
  public function isTermResource(): bool {
    return !empty($this->getTermResource());
  }

  /**
   * Returns appropriate lookup endpoint.
   *
   * @return string
   *   JSONAPI endpoint.
   */
  public function getLookupResource(): string {
    return $this->isTermResource()
      ? $this->getTermResource()
      : $this->getResource();
  }

  /**
   * Given input string returns query for entity lookup.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQuery($input): array {
    return $this->isTermResource()
      ? $this->getLookupQueryTerm($input)
      : $this->getLookupQueryTitle($input);
  }

  /**
   * Builds lookup query for node title search.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQueryTitle($input) {
    return [
      'filter[title][operator]' => 'CONTAINS',
      'filter[title][value]' => $input,
      'page[limit]' => self::LOOKUP_LIMIT,
    ];
  }

  /**
   * Builds lookup query for term name search.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQueryTerm($input) {
    return [
      "filter[name][operator]" => "CONTAINS",
      "filter[name][value]" => $input,
      'page[limit]' => self::LOOKUP_LIMIT,
    ];
  }

  /**
   * Given appropriate item id & max items will fetch content.
   *
   * @param int $id
   *   Entity id (nid or tid depending on source).
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   JSONAPI data.
   */
  public function getContent($id, $limit = 1) {
    if ($this->isTermResource()) {
      return $this->getContentByTerm($id, $limit);
    }
    elseif ($id && $id !== "-1") {
      return $this->getContentByNid($id);
    }
    else {
      return $this->getContentByRecency($limit);

    }
  }

  /**
   * Get URL query for querying content by taxonomy term.
   *
   * @param int $term_id
   *   Term tid.
   * @param int $limit
   *   Max items to fetch.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentbyTermQuery($term_id, $limit = 1) {
    $term_field = $this->getTermField();
    $query = [
      "filter[${term_field}.drupal_internal__tid][value]" => $term_id,
      'include' => $this->getIncludes(),
      'page[limit]' => $limit,
      'sort' => '-created',
    ];
    return $query;
  }

  /**
   * Given appropriate item id & max items will fetch content.
   *
   * @param int $term_id
   *   Term tid.
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   JSONAPI response.
   */
  public function getContentByTerm($term_id, $limit = 1) {
    if ($cache = $this->getContentCache(__FUNCTION__, func_get_args())) {
      return $cache->data;
    }
    else {
      $endpoint = $this->getResource();
      $query = $this->getContentbyTermQuery($term_id, $limit);
      $data = ExternalContentJsonApi::getJsonApi($endpoint, $query);
      $this->setContentCache($data, __FUNCTION__, func_get_args());
      return $data;
    }
  }

  /**
   * Get URL query for querying content by created date.
   *
   * @param int $limit
   *   Max items to fetch.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentbyRecency($limit = 1) {
    if ($cache = $this->getContentCache(__FUNCTION__, func_get_args())) {
      return $cache->data;
    }
    else {
      $endpoint = $this->getResource();
      $query = $this->getContentByRecencyQuery($limit);
      $data = ExternalContentJsonApi::getJsonApi($endpoint, $query);
      $this->setContentCache($data, __FUNCTION__, func_get_args());
      return $data;
    }
  }

  /**
   * Get URL query for querying most recent content.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentByRecencyQuery($limit = 1) {
    return [
      'sort' => '-created',
      'page[limit]' => $limit,
      'include' => $this->getIncludes(),
    ];
  }

  /**
   * Get URL query for querying content by node nid.
   *
   * @param int $nid
   *   Node nid.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentByNidQuery($nid) {
    return [
      "filter[drupal_internal__nid]" => $nid,
      'include' => $this->getIncludes(),
    ];
  }

  /**
   * Given appropriate item id will fetch content.
   *
   * @param int $id
   *   Node nid.
   *
   * @return bool|mixed
   *   JSONAPI response.
   */
  public function getContentByNid($id) {

    if ($cache = $this->getContentCache(__FUNCTION__, func_get_args())) {
      return $cache->data;
    }
    else {
      $endpoint = $this->getResource();
      $query = $this->getContentByNidQuery($id);
      $data = ExternalContentJsonApi::getJsonApi($endpoint, $query);
      $this->setContentCache($data, __FUNCTION__, func_get_args());
      return $data;
    }
  }

  /**
   * Returns content cache key for this class based on method & args.
   *
   * @param string $function
   *   Function name.
   * @param array $args
   *   List of arguments.
   *
   * @return string
   *   A cache key.
   */
  protected function contentCacheKey(string $function, array $args) {
    $source_id = $this->id;
    $key_args = implode('_', $args);
    return "ExternalContentSource:$source_id:$function:$key_args";
  }

  /**
   * Retrieve cache for class function with supplied args.
   *
   * @param string $function
   *   Function name.
   * @param array $args
   *   Function args.
   *
   * @return false|object
   *   Cache item or null.
   */
  protected function getContentCache(string $function, array $args) {
    $cache_key = $this->contentCacheKey($function, $args);
    return \Drupal::cache()->get($cache_key);
  }

  /**
   * Set cache for class function with supplied args.
   *
   * @param object $data
   *   Data to be cached.
   * @param string $function
   *   Function name.
   * @param array $args
   *   Function args.
   *
   * @return mixed
   *   Return from cache::set
   */
  protected function setContentCache($data, string $function, array $args) {
    $cache_key = $this->contentCacheKey($function, $args);
    return \Drupal::cache()->set($cache_key, $data, time() + self::CACHE_TIMEOUT, []);
  }

}
