<?php

namespace Drupal\external_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_content\ExternalContentSourceInterface;
use Drupal\som_api_integration_externalreference\ExternalSourceJsonApi;

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
 *     "list_builder" = "Drupal\external_content\ExternalContentSourceListBuilder",
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
 *     "edit-form" = "/admin/structure/external-content-source/{external_content_source}",
 *     "delete-form" = "/admin/structure/external-content-source/{external_content_source}/delete"
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

  const lookupLimit = 5;

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
      'page[limit]' => self::lookupLimit,
    ];
  }

  public function getLookupQueryTerm($input) {
    return [
      "filter[name][operator]" => "CONTAINS",
      "filter[name][value]" => $input,
      'page[limit]' => self::lookupLimit
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
