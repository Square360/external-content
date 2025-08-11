# External Content

A Drupal module for referencing and displaying external API content.

This module comes with built-in support for including content from other Drupal sites via JSONAPI.
JSONAPI plugins allow you to select content by title or taxonomy term.

New source types can be created by extending the `ExternalContentSourcePluginBase` class.


## Configuration
1. Navigate to **Administration > Configuration > External Content Sources**
2. Configure endpoints & authentication for each source type.

## Usage

### Adding Fields
1. Add an "External Content" field to your entity.
2. Configure **Enabled Sources** to limit available options

## Templating

Override templates for custom display:
- `external-content.html.twig` - Default template
- `external-content--[source-type].html.twig` - Source type specific
- `external-content--[source-id].html.twig` - Individual source specific

## Creating Source Plugins

New plugin types can be created by extending the `ExternalContentSourcePluginBase` class.
Custom options for each source type can be added to via the `buildConfigurationForm` method.
Different plugin types can accommodate different APIs eg search by title, search by term, etc.

## Requirements

- Drupal 9.5+ or Drupal 10+
- PHP 8.1+
