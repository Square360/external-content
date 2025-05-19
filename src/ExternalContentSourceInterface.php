<?php

namespace Drupal\external_content;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an external_content_source entity type.
 */
interface ExternalContentSourceInterface extends ConfigEntityInterface {

  /**
   * Returns ID.
   *
   * @return int|string|null
   *   ID.
   */
  public function getId();

  /**
   * Returns label.
   *
   * @return string
   *   Label.
   */
  public function getLabel();

  /**
   * Returns term resource.
   *
   * @return string
   *   Term resource.
   */
  public function getTermResource();

  /**
   * Returns term field.
   *
   * @return string
   *   Term field name.
   */
  public function getTermField();

  /**
   * Returns string of JSONAPI includes.
   *
   * @return string
   *   JSONAPI include string.
   */
  public function getIncludes();

  /**
   * Returns cache timeout.
   *
   * @return int
   *   Cache timeout.
   */
  public function getCacheTimeout();
  /**
   * Returns resource.
   *
   * @return string
   *   Resource.
   */
  public function getResource();

  /**
   * Returns whether this resource is a simple node resource or node by term.
   *
   * @return bool
   *   True if term resource.
   */
  public function isTermResource(): bool;

  /**
   * Returns appropriate lookup endpoint.
   *
   * @return string
   *   JSONAPI endpoint.
   */
  public function getLookupResource(): string;

  /**
   * Given input string returns query for entity lookup.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQuery($input): array;

  /**
   * Builds lookup query for node title search.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQueryTitle($input);

  /**
   * Builds lookup query for term name search.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   URL Query object array.
   */
  public function getLookupQueryTerm($input);

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
  public function getContent($id, $limit = 1);

  /**
   * Get URL query for querying content by taxonomy term.
   *
   * @param array $term_ids
   *   Term tid.
   * @param int $limit
   *   Max items to fetch.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentbyTermQuery(array $term_ids, $limit = 1);

  /**
   * Get URL query for querying content by taxonomy term.
   *
   * @param array $term_ids
   *   Term tid.
   * @param int $limit
   *   Max items to fetch.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentbyMultiTermQuery(array $term_ids, $limit = 1);

  /**
   * Given appropriate item id & max items will fetch content.
   *
   * @param array $term_ids
   *   Term tid.
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   JSONAPI response.
   */
  public function getContentByTerm(array $term_ids, $limit = 1);

  /**
   * Get URL query for querying content by created date.
   *
   * @param int $limit
   *   Max items to fetch.
   * @param array $extra_arguments
   *   Extra arguments to pass into query.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentbyRecency($limit = 1, $extra_arguments = []);

  /**
   * Get URL query for querying most recent content.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentByRecencyQuery($limit = 1);

  /**
   * Get URL query for querying content by node nid.
   *
   * @param int $nid
   *   Node nid.
   *
   * @return array
   *   JSONAPI URL query object array.
   */
  public function getContentByNidQuery($nid);

  /**
   * Given appropriate item id will fetch content.
   *
   * @param int $id
   *   Node nid.
   *
   * @return bool|mixed
   *   JSONAPI response.
   */
  public function getContentByNid($id);

}
