
$(document).ready(function() {
    var monitorUpload = null;
    $('.upload-form form').submit(function() {
        if (!monitorUpload) {
            $('.upload-progress').show();
            monitorUpload = setInterval(function() {
                $.getJSON(session_upload_progress, function(data) {
                    if (Array.isArray(data) && data.length) {
                        var processed = data.bytes_processed;
                        var total = data.content_length;
                        var pct = Math.round(processed*100.0/(1.0*total));
                        var text = '';
                        text += Math.round(processed/(1024*1024))+'/';
                        text += Math.round(total/(1024*1024))+'Mo<br/>';
                        text += pct+'%';
                        $('.upload-progress span').html(text);
                        $('.upload-progress .progress-bar').css('width', pct+'%');
                    }
                });
            }, 1000);
        }
        return true;
    });
});
