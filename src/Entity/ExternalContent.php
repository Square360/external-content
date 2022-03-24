<?php

namespace Drupal\external_content\Entity;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_content\ExternalContentInterface;
use Drupal\som_api_integration_externalreference\ExternalSourceJsonApi;

/**
 * Defines the ExternalContent configuration entity.
 *
 * @ConfigEntityType(
 *   id = "external_content",
 *   label = @Translation("External Content"),
 *   handlers = {
 *     "list_builder" = "Drupal\external_content\ExternalContentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\external_content\Form\ExternalContentForm",
 *       "edit" = "Drupal\external_content\Form\ExternalContentForm",
 *       "delete" = "Drupal\external_content\Form\ExternalContentDeleteForm",
 *     }
 *   },
 *   config_prefix = "external_content",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "resource",
 *     "term_resource",
 *     "term_field",
 *     "includes",
 *   },
 *   links = {
 *     "edit-form" =
 *   "/admin/config/system/external_content/{external_content}",
 *     "delete-form" =
 *   "/admin/config/system/external_content/{external_content}/delete",
 *   }
 * )
 */
class ExternalContent extends ConfigEntityBase implements ExternalContentInterface {

  const lookupLimit = 5;

  /**
   * The ExternalContent ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ExternalContent label.
   *
   * @var string
   */
  protected $label;

  /**
   * JSONAPI Resource endpoint
   *
   * @var string
   */
  protected $resource;

  /**
   * Optional term bundle to extract list of terms
   *
   * @var string
   */
  protected $term_resource;

  /**
   * Optional term if this source should be filtered by term
   *
   * @var string
   */
  protected $term_field;

  /**
   * Optional list of includes to request related data from JSONAPI
   *
   * @var string
   */
  protected $includes;

  public function getId() {
    return $this->id();
  }

  public function getLabel() {
    return $this->label;
  }

  public function getTermResource() {
    return $this->term_resource;
  }

  public function getTermField() {
    return $this->term_field;
  }

  public function getIncludes() {
    return $this->includes;
  }

  public function getResource() {
    return $this->resource;
  }

  /**
   * Returns whether this resource is a simple node resource or node by term.
   *
   * @return bool
   */
  public function isTermResource(): bool {
    return !empty($this->getTermResource());
  }

  public function getLookupResource(): string {
    return $this->isTermResource()
      ? $this->getTermResource()
      : $this->getResource();
  }

  public function getLookupQuery($input): array {
    return $this->isTermResource()
      ? $this->getLookupQueryTerm($input)
      : $this->getLookupQueryTitle($input);
  }

  public function getLookupQueryTitle($input) {
    return [
      'filter[title][operator]' => 'CONTAINS',
      'filter[title][value]' => $input,
      'page[limit]' => ExternalContent::lookupLimit,
    ];
  }

  public function getLookupQueryTerm($input) {
    return [
      "filter[name][operator]" => "CONTAINS",
      "filter[name][value]" => $input,
      'page[limit]' => ExternalContent::lookupLimit
    ];
  }

  public function getContentbyTermQuery($term_id, $limit = 1) {
    $term_field = $this->getTermField();
    $query = [
      "filter[${term_field}.drupal_internal__tid][value]" => $term_id,
      'include' => $this->getIncludes(),
      'page[limit]' => $limit,
      'sort' => '-created'
    ];
    return $query;
  }

  public function getContent($id, $limit=1) {

    if ($this->isTermResource()) {
      return $this->getContentByTerm($id, $limit);
    }
    else {
      return $this->getContentByNid($id);
    }
  }

  public function getContentByTerm($term_id, $limit = 1) {
    $endpoint = $this->getResource();
    $query = $this->getContentbyTermQuery($term_id, $limit);
    $data = ExternalSourceJsonApi::getJsonApi($endpoint, $query);
    return $data;
  }

  public function getContentByNidQuery($id) {
    return [
      "filter[drupal_internal__nid]" => $id,
      'include' => $this->getIncludes(),
    ];
  }

  public function getContentByNid($id) {
    $endpoint = $this->getResource();
    $query = $this->getContentByNidQuery($id);
    $data = ExternalSourceJsonApi::getJsonApi($endpoint, $query);
    return $data;
  }
}
