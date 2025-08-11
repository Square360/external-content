# External Content

A Drupal module providing a framework for referencing and displaying external API content.

## Purpose

This module allows you to pull and cache specific content dynamically without having to import large content sets using the feeds or migration modules. It reduces development costs by eliminating custom imports and eliminates redundant data by selecting only the specific content which is required.

The module comes with plugins to consume content from other Drupal sites via JSONAPI. New plugin types can be created to support other APIs.

## Example Use Cases

- **Reference Specific Content**: A content author wants to select specific articles from an external site and display am up-to-date teaser on the page.
- **Filtered Content Blocks**: A content author wants to add a block with the 3 most recent articles tagged with "Cats" or "Capybaras" from an external site

## Configuration
1. Navigate to **Administration > Configuration > External Content Sources**
2. Configure endpoints & authentication for each source type.

New source types can be created by extending the `ExternalContentSourcePluginBase` class.

### Adding Fields
1. Add an "External Content" field to your entity.
2. Configure **Enabled Sources** to limit available options.
3. Choose the field cardinality, allow_multiple_values (widget) & max displayed items (formatter) to best suit the use case.

## Templating

Override templates for custom display:
- `external-content.html.twig` - Default template
- `external-content--[source-type].html.twig` - Source type specific
- `external-content--[source-id].html.twig` - Individual source specific

## Creating Source Plugins

New plugin types can be created by extending the `ExternalContentSourcePluginBase` class.
Custom options for each source type can be added to via the `buildConfigurationForm` method.
An autocompleteHandler must be implemented to allow content authors to conveniently select content.
Different plugin types can accommodate different APIs eg search by title, search by term, etc.

## Requirements

- Drupal 9.5+ or Drupal 10+
- PHP 8.1+
