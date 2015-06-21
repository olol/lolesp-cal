$(document).ready(function() {
    $.ajax({
        url: 'web/js/calendars.json',
        success: function(calendars) {
            $.each(['all', 'team', 'region', 'tournament'], function(tidx, type) {
                var $list = $('#calendar-' + type + '-list');
                var s = type == 'tournament' ? 's12' : 's2';
                $.each(calendars[type], function(lidx, link) {
                    $list.append('<a class="col ' + s + ' waves-effect waves-light btn-large purple lighten-1 modal-trigger" href="#modal-download" style="margin:2px" data-ics="' + link.url + '">' + link.name + '</a>');
                });
            });

            $('.modal-trigger')
              .leanModal()
              .click(function() {
                var url = $(this).attr('data-ics');
                $('#ical-url').html(url);
                $('#gcal-url').attr('href', 'http://www.google.com/calendar/render?cid=' + url);
                $('#dl-url').attr('href', url);
            });

        },
        dataType: 'json'
    });
});
