<?php

namespace Drupal\external_content;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Contains all code specifically related to requesting & processing JSONAPI.
 */
class ExternalContentJsonApi {

  /**
   * Extracts a single file image (not media) with fieldname.
   *
   * @param mixed $json
   *   Just the JSON data.
   * @param mixed $jsonapi_request
   *   The full JSON object returned from the external source.
   * @param mixed $field_name
   *   The field name to return.
   *
   * @return array
   *   Array of image values.
   */
  public static function extractFileImage($json, $jsonapi_request, $field_name) {
    $image = $json["relationships"][$field_name]["data"];
    $image_file = ExternalSourceJsonApi::getIncludedDataById($jsonapi_request, $image['id']);
    $site = ExternalSourceJsonApi::getSiteFromUrl($json["links"]["self"]["href"]);

    $external_image = [
      'url' => $site . $image_file["attributes"]["uri"]["url"],
      'filename' => $image_file["attributes"]["filename"] ?? '',
      'alt' => $image["meta"]["alt"] ?? '',
      'width' => $image["meta"]["width"] ?? '',
      'height' => $image["meta"]["height"] ?? '',
      'title' => $image["meta"]["title"] ?? '',
    ];
    return $external_image;
  }

  /**
   * Extracts a single media image from the given field name.
   *
   * @param mixed $json
   *   Just the JSON data.
   * @param mixed $jsonapi_request
   *   The full JSON object returned from the external source.
   * @param mixed $field_name
   *   The field name to return.
   *
   * @return array
   *   Array of image values.
   */
  public static function extractMediaImage($json, $jsonapi_request, $field_name) {

    $field_image_id = $json["relationships"][$field_name]["data"]["id"];
    $image = ExternalSourceJsonApi::getIncludedDataById($jsonapi_request, $field_image_id);
    $image_file_id = $image["relationships"]["field_media_image"]["data"]["id"];
    $image_file = ExternalSourceJsonApi::getIncludedDataById($jsonapi_request, $image_file_id);

    $site = ExternalSourceJsonApi::getSiteFromUrl($json["links"]["self"]["href"]);
    $external_image = [
      'url' => $site . $image_file["attributes"]["uri"]["url"],
      'consumers' => $image_file["links"],
      'caption' => $image["attributes"]["field_media_caption"] ?? '',
      'alt' => $image["relationships"]["field_media_image"]["data"]["meta"]["alt"] ?? '',
      'width' => $image["relationships"]["field_media_image"]["data"]["meta"]["width"] ?? '',
      'height' => $image["relationships"]["field_media_image"]["data"]["meta"]["height"] ?? '',
      'filename' => $image_file["attributes"]["filename"] ?? '',
      'title' => $image_file["attributes"]["title"] ?? '',
      'filemime' => $image_file["attributes"]["filemime"] ?? '',
    ];
    return $external_image;
  }

  /**
   * Return the available external sources.
   *
   * @return array
   *   Sources array
   */
  public static function getReferenceSources() {
    $env = (
    defined('PANTHEON_ENVIRONMENT')
    && PANTHEON_ENVIRONMENT == 'live'
    ) ? 'live' : 'dev';

    $sources['insights_article'] = [
      'entity_label' => 'Insights Article',
      'source_label' => 'Yale Insights',
      'resource' => \Drupal::state()->get('yaleinsights_article_path_' . $env),
      'include' => 'field_body_m,field_image,field_image.field_media_image,field_experts_m,node_type',
      'terms' => [
        'topics' => [
          'label' => 'Insights Article by Topic',
          'resource' => \Drupal::state()->get('yaleinsights_topic_path_' . $env),
          'field' => 'field_topics_m',
        ],
        'departments' => [
          'label' => 'Insights Articles by Department',
          'resource' => \Drupal::state()->get('yaleinsights_department_path_' . $env),
          'field' => 'field_department',
        ],
      ],
    ];

    return $sources;
  }

  /**
   * Simple function to extract site from an url.
   *
   * @param array $entity
   *   The entity.
   *
   * @return string
   *   A URL string.
   */
  public static function getEntityUrl(array $entity) {
    $resource = parse_url($entity["links"]["self"]["href"]);
    $alias = $entity["attributes"]["path"]["alias"];
    $url = $resource['scheme'] . '://' . $resource['host'] . $alias;
    return $url;
  }

  /**
   * Extracts associated included data given the id.
   *
   * @param mixed $jsonapi_request
   *   The JSON response.
   * @param string $id
   *   The ID.
   *
   * @return mixed
   *   Returns entity.
   */
  public static function getIncludedDataById($jsonapi_request, $id) {
    $included = $jsonapi_request['included'] ?? [];
    foreach ($included as $entity) {
      if ($entity['id'] == $id) {
        return $entity;
      }
    }
  }

  /**
   * Will make a query to a JSON api Resource. Optional query variables.
   *
   * @param string $endpoint
   *   Path to JSON resource.
   * @param array $query
   *   Query parameters to pass.
   * @param bool $skip_cache
   *   If true will ignore any existing cache value.
   *
   * @return mixed|bool
   *   A JSON array
   */
  public static function getJsonApi($endpoint, array $query = [], bool $skip_cache = FALSE) {
    $query_str = UrlHelper::buildQuery($query);
    $url = urldecode($endpoint . '?' . $query_str);

    $cache_key = 'jsonapi:' . $url;
    if (!$skip_cache && $cache = \Drupal::cache()->get($cache_key)) {
      $data = $cache->data;
    }
    else {
      $request = \Drupal::httpClient()->get($url);
      if ($request->getStatusCode() == 200) {
        $response = $request->getBody();
        $data = Json::decode($response);
        \Drupal::cache()->set($cache_key, $data, time() + 60 * 60 * 3);
      }
      else {
        $message = 'Cannot get resource: ' . $request->getStatusCode() . ' : ' . $url;
        \Drupal::logger('som_api_integration_external_reference')->error($message);
        return FALSE;
      }
    }
    return $data;

  }

  /**
   * Returns simple link to page if given jsonapi entity.
   *
   * @param mixed $jsonapi
   *   JSON object.
   *
   * @return link
   *   A link
   */
  public static function getLinkFromEntity($jsonapi) {
    $endpoint = $jsonapi["links"]["self"]["href"];
    $alias = $jsonapi["attributes"]["path"]["alias"];
    $url = ExternalSourceJsonApi::getSiteFromUrl($endpoint) . $alias;
    $title = $jsonapi["attributes"]["title"];
    $link = Link::fromTextAndUrl($title, Url::fromUri($url))->toString();
    return $link;
  }

  /**
   * Given search resource and nid, will return node entity.
   *
   * @param string $source_key
   *   Endpoint to be queried.
   * @param int $nid
   *   Node ID on endpoint source.
   *
   * @return mixed|bool
   *   Node JSONAPI entity (cached)
   */
  public static function getNodeByNid($source_key, $nid) {
    $sources = ExternalSourceJsonApi::getReferenceSources();
    $source = $sources[$source_key];
    $endpoint = $source['resource'];
    $includes = $source['include'];
    $query = [
      "filter[drupal_internal__nid]" => $nid,
      'include' => $includes,
    ];
    $json = ExternalSourceJsonApi::getJsonApi($endpoint, $query, FALSE);
    return $json;
  }

  public static function validate($is, $source_id) {

  }

  /**
   * Get recent nodes from endpoint.
   *
   * @param string $source_key
   *   Endpoint to be queried.
   * @param int $limit
   *   Number of items to return.
   *
   * @return mixed|bool
   *   Returns JSON object or FALSE if empty.
   */
  public static function getRecentNodes($source_key, $limit = 10) {

    $sources = ExternalSourceJsonApi::getReferenceSources();
    $source = $sources[$source_key];
    $endpoint = $source['resource'];
    $query = [
      'page[limit]' => $limit,
      'sort' => '-created',
      'include' => $source['include'],
    ];
    $json = ExternalSourceJsonApi::getJsonApi($endpoint, $query);
    return $json;
  }

  /**
   * Simple function to extract site from a url.
   *
   * @param string $url
   *   A URL string to be parsed.
   *
   * @return string
   *   A URL string
   */
  public static function getSiteFromUrl($url) {
    $resource = parse_url(stripslashes($url));
    $url = $resource['scheme'] . '://' . $resource['host'];
    return $url;
  }

  /**
   * Given an endpoint will return the source details.
   *
   * @param mixed $endpoint
   *   The endpoint.
   *
   * @return bool|mixed
   *   Returns array or FALSE.
   */
  public static function getSourceDataFromEndpoint($endpoint) {
    $sources = ExternalSourceJsonApi::getReferenceSources();
    foreach ($sources as $name => $source) {
      if (strpos($endpoint, $source['resource']) === 0) {
        return array_merge($sources[$name], ['id' => $name]);
      }
    }
    $message = 'Unknown resource: ' . $endpoint;
    \Drupal::logger('som_api_integration_external_reference')->notice($message);
    return FALSE;
  }

}
