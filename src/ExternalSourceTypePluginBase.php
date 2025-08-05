<?php

declare(strict_types=1);

namespace Drupal\external_content;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for external_source_type plugins.
 */
abstract class ExternalSourceTypePluginBase extends PluginBase implements ExternalSourceTypeInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
