(function ($, Drupal) {
  Drupal.behaviors.message_dialogs = {
    attach: function (context, settings) {
      var mid = drupalSettings.messageData.mid;
      var uid = drupalSettings.messageData.uid;

      $(window)
      .once('message_dialogs')
      .on('dialog:aftercreate', function (event, dialog, $element) {

        var url = '/ajax/message/' + mid + '/' + uid + '/mark-read';
        Drupal.ajax({url: url}).execute();

        $("tr.message-row-" + mid).find("a").removeClass("message-status-unread");
      });
    }
  };
})(jQuery, Drupal);
