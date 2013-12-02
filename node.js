(function ($) {
  Drupal.behaviors.unlAccessSummary = {
    attach: function(context) {
      $('#edit-unl-access', context).drupalSetSummary(function(context) {
        var vals = [];
        $('#edit-unl-access-affiliations input[type=checkbox]', context).each(function(context) {
          var checkbox = $(this);
          if (checkbox.is(':checked')) {
            vals.push(checkbox.next().text().trim());
          }
        });
        if (vals.length == 0) {
          return Drupal.t('Public');
        }
        return Drupal.t('Restricted to: ') + vals.join(', ');
      });
    }
  };
})(jQuery);
