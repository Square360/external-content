<?php

namespace Drupal\external_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_content\ExternalContentInterface;

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
 *     "term",
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

  public function getTermField() {
    return $this->term_field;
  }

  public function getIncludes() {
    return $this->includes;
  }

  public function getResource() {
    return $this->resource;
}
  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}