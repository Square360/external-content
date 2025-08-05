<?php

declare(strict_types=1);

namespace Drupal\external_content;

/**
 * Interface for external_source_type plugins.
 */
interface ExternalSourceTypeInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Provides a form for configuring the external source type.
   *
   * @param array &$form_container
   *   The form container.
   * @param array &$plugin_configuration
   *   The plugin configuration.
   *
   * @return array
   *   The form array.
   */
  public function externalSourceConfigForm(array &$form_container, array &$plugin_configuration);


}
