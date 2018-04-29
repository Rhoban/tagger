var patches = null;
var can_click = true;
var toCancel = null;
var reviewing = false;

function displayPatches()
{
    var html = '';
    var w = (128+6)*(patchesCol);

    html += '<div class="patches noselect" style="max-width:'+(w)+'px">';
    for (var k in patches) {
        var patch = patches[k];
        patch[2] = 0;
        html += '<div rel="'+k+'" class="patch-container patch-container-'+patch[0]+'">';
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
    if (toTagUser != 0 || training) {
        $('.tag-ok').show();
        $('.tag-zone').show();
        $('.tag-well-done').hide();
    } else {
        $('.tag-ok').hide();
        $('.tag-zone').hide();
        $('.tag-well-done').show();
    }

    if (!training) {
        if (toCancel) {
            $('.cancel-last').show();
        } else {
            $('.cancel-last').hide();
        }

        $('.tag-progress').show();
        var pct = (100*(toTag-toTagUser)/toTag).toFixed(2);
        $('.tag-progress .progress-bar').css('width', pct+'%');
        $('.tag-progress span').text(pct+'%');

        if (toTagUserNoConsensus) {
            $('.contributions').text(toTagUserNoConsensus+' useful remaining');
        } else {
            $('.contributions').text('improving quality');
        }

        $('.tag-team-progress').show();
        var pctTeam = (100*(toTag-toTagTeam)/toTag).toFixed(2);
        $('.tag-team-progress .progress-bar').css('width', pctTeam+'%');
        $('.tag-team-progress span').text(pctTeam+'%');
    } else {
        updateTrainBar();
        $('.cancel-last').hide();
        $('.contributions').text('training');
    }
}

function updateTrainBar()
{
    $('.tag-progress').show();
    var pct = trainProgress*100;
    $('.tag-progress .progress-bar').css('width', pct.toFixed(2)+'%');
    $('.tag-progress span').text(pct.toFixed(2)+'%');
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

            var data = {};
            for (var k in toSave) {
                data[toSave[k][0]] = toSave[k][2];
            }

            if (training) {
                if (reviewing) {
                    updatePatches();
                    reviewing = false;
                } else {
                    $.post(review_url, data, function(json) {
                        if (json.trained) {
                            $('.tag-progress').hide();
                            $('.tag-ok').hide();
                            $('.tag-zone').hide();
                            $('.tag-well-done').show();
                        } else {
                            trainProgress = json.progress;
                            updateTrainBar();

                            for (var id in json.patches) {
                                var div = $('.patch-container-'+id);
                                if (json.patches[id]) {
                                    div.addClass('review-ok');
                                } else {
                                    div.addClass('review-ko');
                                }

                                reviewing = true;
                            }
                        }
                    });
                }
            } else {
                $.post(send_url, data, function(json) {
                    updatePatches();
                    toTagUser = json[0];
                    toTagUserNoConsensus = json[1];
                    toTagTeam = json[2];
                    toCancel = json[3];
                    updateProgress();
                });
            }
        }

        return false;
    });

    if (!training) {
        $('.tag-cancel').click(function() {
            $.post(cancel_url, {'tags': toCancel}, function(json) {
                toCancel = null;
                toTagUser = json[0];
                toTagUserNoConsensus = json[1];
                toTagTeam = json[2];
                updateProgress();
            });

            return false;
        });
    }
});
