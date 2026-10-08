(function($, Drupal, drupalSettings){
  'use strict';

  // @see https://select2.github.io/options.html
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.select2 = Drupal.webform.select2 || {};
  Drupal.webform.select2.options = Drupal.webform.select2.options || {};
  Drupal.webform.select2.options.tags = 'true';
  Drupal.webform.select2.options.tokenSeparators = [',',';'];
  Drupal.webform.select2.options.width = Drupal.webform.select2.options.width || '100%';
  Drupal.webform.select2.options.widthInline = Drupal.webform.select2.options.widthInline || '50%';

  Drupal.behaviors.ldbaseWebformWithSelect2 = {
    attach: function (context, settings) {
      $(context).find('.input-comma-check').on('keyup', function(e) {
        if (e.which == 188) {
          alert('You added a comma. Please only enter one item per line.  \nAdd additional lines if needed.');
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
