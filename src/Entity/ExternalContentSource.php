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
 *     "type",
 *     "resource",
 *     "cache_timeout",
 *     "plugin_configuration"
 *   }
 * )
 */
class ExternalContentSource extends ConfigEntityBase implements ExternalContentSourceInterface {

  const LOOKUP_LIMIT = 5;

  /**
   * The ExternalContentSource ID.
   *
   * @var string
   */
  protected $id;

  /**
   * External source type.
   *
   * @var string
   */
  protected $type;

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
   * Optional list of includes to request related data from JSONAPI.
   *
   * @var string
   */
  protected $includes;

  /**
   * Cache timeout.
   *
   * @var int
   */
  protected int $cache_timeout = 0;

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected $plugin_configuration = [];

  /**
   * Returns ID.
   *
   * @return int|string|null
   *   ID.
   */
  public function getId() {
    return $this->id;
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
   * Returns string of JSONAPI includes.
   *
   * @return string
   *   JSONAPI include string.
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * Returns cache timeout.
   *
   * @return int
   *   Cache timeout.
   */
  public function getCacheTimeout() {
    return $this->cache_timeout;
  }

  /**
   * Returns type.
   *
   * @return string
   *   External source type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns plugin configuration.
   *
   * @return array
   *   Plugin configuration array.
   */
  public function getPluginConfiguration() {
    return $this->plugin_configuration ?: [];
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
   * Given appropriate item id & max items will fetch content.
   *
   * @param int $id
   *   Entity id (nid or tid depending on source).
   * @param int $limit
   *   Max number of items to return.
   *
   * @return bool|mixed
   *   External content data.
   */
  public function getContent($id, $limit = 1) {
    if ($cache = $this->getContentCache(__FUNCTION__, func_get_args())) {
      return $cache->data;
    }

    $plugin_type = $this->getType();
    if ($plugin_type) {
      try {
        $plugin_manager = \Drupal::service('plugin.manager.external_source_type');
        $plugin = $plugin_manager->createInstance($plugin_type);
        $data = $plugin->getContent($this, $id, $limit);
        $this->setContentCache($data, __FUNCTION__, func_get_args());
        return $data;
      } catch (\Exception $e) {
        \Drupal::logger('external_content')->error('Error getting content for source @source_id: @error', [
          '@source_id' => $this->id,
          '@error' => $e->getMessage(),
        ]);
        return FALSE;
      }
    }

    return FALSE;
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
    $plugin_type = $this->getType();
    if ($plugin_type) {
      try {
        $plugin_manager = \Drupal::service('plugin.manager.external_source_type');
        $plugin = $plugin_manager->createInstance($plugin_type);
        return $plugin->getLookupQuery($this, $input);
      } catch (\Exception $e) {
        \Drupal::logger('external_content')->error('Error getting lookup query for source @source_id: @error', [
          '@source_id' => $this->id,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return [];
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
    $key_args = json_encode($args);
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
    $cache_timeout = time() + $this->getCacheTimeout();
    return \Drupal::cache()->set($cache_key, $data, $cache_timeout, []);
  }



}
