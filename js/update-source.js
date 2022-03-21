/**
 * @file
 * Attaches behaviors for the Comment module's "X new comments" link.
 *
 * May only be loaded for authenticated users, with the History module
 * installed.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  console.log('updaet source');
  Drupal.behaviors.externalContentUpdateSource = {
    attach: function attach(context) {
      const fields = context.querySelectorAll('.field--widget-external-content-default');

      fields.forEach((field) => {
        const sourceField = field.querySelector('.external-content__source-selector');
        const searchField = field.querySelector('.external-content__search');
        console.log(field);
        sourceField.addEventListener('change', (event) => {
          const path = searchField.getAttribute('data-autocomplete-path');
          const rgx = /\bsource_id=([^&]+)/i;
          const newPath = path.replace(rgx, `source_id=${event.target.value}`)
          console.log(path, newPath);
          searchField.setAttribute('data-autocomplete-path', newPath);
        });

      });
    }
  };
})(jQuery, Drupal, drupalSettings);
