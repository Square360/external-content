<?php

/**
 * @file
 * Hooks specific to the External Content module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the headers used when querying external sources.
 *
 * @param array &$headers
 *   An array of request headers, passed by reference.
 * @param \Drupal\external_content\Entity\ExternalContentSource $source
 *   The external content source.
 */
function hook_external_content_headers_alter(array &$headers, \Drupal\external_content\Entity\ExternalContentSource $source): void {
  // Alter the headers as needed.
}

/**
 * Alter the parameters when querying external sources.
 *
 * @param array &$query
 *   An array of parameters, passed by reference.
 * @param \Drupal\external_content\Entity\ExternalContentSource $source
 *   The external content source.
 */
function hook_external_content_query_alter(array &$query, \Drupal\external_content\Entity\ExternalContentSource $source): void {
  // Alter the query as needed.
}

/**
 * @} End of "addtogroup hooks".
 */
