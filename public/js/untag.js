
$(document).ready(function() {
    $('.mini-patch').click(function() {
        if (confirm('Untag this?')) {
            var id = $(this).attr('rel');
            var div = $(this);
            $.get(untag_url+'?patch='+id, function(r) {
                if (r == 'ok') {
                    div.css('opacity', 0.33);
                }
            });
        }
    });
});
