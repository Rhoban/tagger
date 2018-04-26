var patches = null;
var can_click = true;
var toCancel = null;

function displayPatches()
{
    var html = '';

    html += '<div class="patches noselect">';
    for (var k in patches) {
        var patch = patches[k];
        patch[2] = 0;
        html += '<div rel="'+k+'" class="patch-container">';
        html += '<div class="patch-info patch-info-'+k+'"></div>';
        html += '<img rel="'+k+'" class="patch" width="128" height="128" src="'+patch[1]+'" />';
        html += '</div>';
    }
    html += '</div>';

    $('.tag-zone').html(html);

    $('.patch-container').click(function() {
        var id = parseInt($(this).attr('rel'));
        patches[id][2] += 1;
        if (patches[id][2] > 2) {
            patches[id][2] = 0;
        }

        $('.patch-info-'+id).removeClass('patch-info-ok');
        $('.patch-info-'+id).removeClass('patch-info-unknown');
        switch (patches[id][2]) {
            case 0:
            break;
            case 1:
            $('.patch-info-'+id).addClass('patch-info-ok');
            break;
            case 2:
            $('.patch-info-'+id).addClass('patch-info-unknown');
            break;
        }
    });
}

function updateProgress()
{
    if (toTagUser != 0) {
        $('.tag-ok').show();
        $('.tag-zone').show();
        $('.tag-well-done').hide();
    } else {
        $('.tag-ok').hide();
        $('.tag-zone').hide();
        $('.tag-well-done').show();
    }

    if (toCancel) {
        $('.cancel-last').show();
    } else {
        $('.cancel-last').hide();
    }

    $('.tag-progress').show();
    var pct = Math.round(100*(toTag-toTagUser)/toTag);
    $('.tag-progress .progress-bar').css('width', pct+'%');
    $('.tag-progress span').text(pct+'%');
}

function updatePatches()
{
    updateProgress();

    can_click = false;
    $('.tag-zone').css('opacity', 0.5);
    $.getJSON(patches_url, function(data) {
        patches = data;
        displayPatches();
        $('.tag-zone').css('opacity', 1);
        can_click = true;
    });
}

$(document).ready(function() {
    updatePatches();

    $('.tag-ok').click(function() {
        if (can_click) {
            var toSave = patches;
            updatePatches();

            var data = {};
            for (var k in toSave) {
                data[toSave[k][0]] = toSave[k][2];
            }

            $.post(send_url, data, function(json) {
                toTagUser = json[0];
                toCancel = json[1];
                updateProgress();
            });
        }

        return false;
    });

    $('.tag-cancel').click(function() {
        $.post(cancel_url, {'tags': toCancel}, function(data) {
            toCancel = null;
            json = JSON.parse(data);
            toTagUser = data;
            updateProgress();
        });

        return false;
    });
});