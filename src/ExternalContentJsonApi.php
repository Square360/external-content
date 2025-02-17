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
    $image_file = self::getIncludedDataById($jsonapi_request, $image['id']);
    $site = self::getSiteFromUrl($json["links"]["self"]["href"]);

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
    $image = self::getIncludedDataById($jsonapi_request, $field_image_id);
    $image_file_id = $image["relationships"]["field_media_image"]["data"]["id"];
    $image_file = self::getIncludedDataById($jsonapi_request, $image_file_id);

    $site = self::getSiteFromUrl($json["links"]["self"]["href"]);
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
   * @param array $headers
   *   Headers to be added to the request.
   * @param string|null $source_id
   * *   Source string (allows altering by source.)
   *
   * @return mixed|bool
   *   A JSON array
   */
  public static function getJsonApi($endpoint, array $query = [], array $headers = []) {
    $query_str = UrlHelper::buildQuery($query);
    $query_str = preg_replace('/%5B[0-9]+%5D/simU', '', $query_str);

    $url = urldecode($endpoint . '?' . $query_str);

    $request = \Drupal::httpClient()->get($url, [
      'headers' => $headers
    ]);

    if ($request->getStatusCode() == 200) {
      $response = $request->getBody();
      $data = Json::decode($response);
      return $data;
    }
    else {
      $message = 'Cannot get resource: ' . $request->getStatusCode() . ' : ' . $url;
      \Drupal::logger('som_api_integration_external_reference')->error($message);
      return FALSE;
    }
  }

  /**
   * Returns simple link to page if given jsonapi entity.
   *
   * @param mixed $jsonapi
   *   JSON object.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A link
   */
  public static function getLinkFromEntity($jsonapi) {
    $url = self::getUrlFromEntity($jsonapi);
    $title = $jsonapi["attributes"]["title"];
    $link = Link::fromTextAndUrl($title, Url::fromUri($url))->toString();
    return $link;
  }

  /**
   * Extracts aliased url from jsonapi entity.
   *
   * @param mixed $jsonapi
   *   JSON object.
   *
   * @return string
   *   A string containing url
   */
  public static function getUrlFromEntity($jsonapi) {
    $endpoint = $jsonapi["links"]["self"]["href"];
    $alias = $jsonapi["attributes"]["path"]["alias"];
    $url = self::getSiteFromUrl($endpoint) . $alias;
    return Url::fromUri($url)->toString();
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

}
