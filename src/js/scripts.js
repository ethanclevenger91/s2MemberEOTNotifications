jQuery(document).ready(function($) {
  $('#eotnot-settings-form').parsley();
  $('.eotnot_reminders_table>tbody>tr:last-child').remove();

  $('.eotnot-notification-title:not(.eotnot-notification-title--new)').click(function() {
    $(this).toggleClass('active').closest('tr').next('tr').slideToggle();
  });
  $('input[name="eotnot_options[][eotnot_reminder_title]"]').change(function() {
    $(this).closest('.eotnot-notification-content').prev('tr').find('h4').html($(this).val());
  });
  $('.eotnot-add-another').click(function() {
    var data = {
      'action':'eotnot_row_markup',
      'eotnot_index':$('.form-table tr.eotnot-notification-content').length
    };
    $.post(ajax_object.ajax_url, data, function(response) {
      $('.eotnot_reminders_table').append(response);
    })
  });
})
