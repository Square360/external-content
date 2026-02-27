# External Content

A Drupal module providing a framework for referencing and displaying external API content.

## Purpose

This module allows you to pull and cache content dynamically without having to import large content sets using the feeds or migration modules. It reduces development costs by eliminating custom imports and eliminates redundant data by selecting only the specific content which is required.

The module comes with plugins to consume content from other Drupal sites via JSONAPI. New plugin types can be created to support other APIs.

## Example Use Cases

- **Reference Specific Content**: A content author wants to select specific articles from an external site and display am up-to-date teaser on the page.
- **Filtered Content Blocks**: A content author wants to add a block with the 3 most recent articles tagged with "Cats" or "Capybaras" from an external site.
- **Select Terms for Custom Use**: Use the fields to select items (eg terms) but build a custom query & data processing.

## Configuration
1. Navigate to **Administration > Configuration > External Content Sources**
2. Configure endpoints & authentication for each source type.

New source types can be created by extending the `ExternalContentSourcePluginBase` class.

### Adding Fields
1. Add an "External Content" field to your entity.
2. Configure **Enabled Sources** to limit available options.
3. Choose the field cardinality, allow_multiple_values (widget) & max displayed items (formatter) to best suit the use case.

### Quantity Control

The widget can be configured to allow content authors to specify how many items to fetch from the external source:

1. **Enable Quantity Selection**: Check this option in the field widget settings
2. **Set Maximum Quantity**: Define an upper limit (0 = unlimited)
3. **Author Control**: Authors will see a "Quantity" field when editing content

The formatter's "limit" setting acts as a final cap on results. If both are set, the lower value is used.

**Example:** Author requests 10 items, but formatter limit is 5 → displays 5 items

**Note:** If quantity is not set by the author, the system will use the formatter's limit setting.

### Provided View Formatters
- ExternalContentJsonFormatter - Displays the raw JSON data from the source for debugging.
- ExternalContentPreviewFormatter - Displays a preview of the content which would be displayed. Intended for backend.
- ExternalContentTemplateFormatter - Renders the source content using the theme system.
- ExternalContentViewDataFormatter - Displays the stored values. Useful for backend where the content will be used in custom code.

## Templating

Override templates for custom display:
- `external-content.html.twig` - Default template
- `external-content--[source-type].html.twig` - Source type specific
- `external-content--[source-id].html.twig` - Individual source specific

## Creating Source Plugins

New plugin types can be created by extending the `ExternalContentSourcePluginBase` class.
Custom options for each source type can be added to via the `buildConfigurationForm` method.
An `autocompleteHandler` must be implemented to allow content authors to conveniently select content.
Different plugin types can accommodate different APIs eg search by title, search by term, etc.
