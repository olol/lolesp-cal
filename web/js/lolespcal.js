lolespcal = {
    search: function(type, val) {
        $('#calendar-' + type + '-list a').each(function() {
            $this = $(this);
            if ($this.text().toLowerCase().indexOf(val.toLowerCase()) === -1) {
                $this.hide();
            } else {
                $this.show();
            }
        });
    }
};

$(document).ready(function() {

    $.ajax({
        url: 'web/js/calendars.json',
        success: function(calendars) {
            $.each(['team', 'region', 'tournament'], function(tidx, type) {
                var $list = $('#calendar-' + type + '-list');
                var s = type == 'tournament' ? 's5' : 's2';
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

            $.each(['team', 'tournament'], function(idx, type) {
                lolespcal.search(type, $('#search-' + type).val());
            });

        },
        dataType: 'json'
    });

    $.each(['team', 'tournament'], function(idx, type) {
        $('#search-' + type).keyup(function() {
            lolespcal.search(type, $(this).val());
        });
    });

    $(".button-collapse").sideNav();

});
