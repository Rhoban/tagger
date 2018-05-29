var patches = null;
var can_click = true;
var to_cancel = null;
var reviewing = false;

function displayPatches()
{
    // Genrating the HTML for the tag zone
    var html = '';
    var w = (patches_size+6)*(patches_col);

    html += '<div class="patches noselect" style="max-width:'+(w)+'px">';
    for (var k in patches) {
        var patch = patches[k];
        patch[2] = 0;
        html += '<div rel="'+k+'" class="patch-container patch-container-'+patch[0]+'">';
        html += '<div class="patch-info patch-info-'+k+'"></div>';
        html += '<img rel="'+k+'" class="patch" style="width:'+patches_size+'px; height:auto" src="'+patch[1]+'" />';
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

    var scale = patches_size/128.0;
    var margin = 8*scale;
    $('.patch-info').css('margin-left', margin+'px');
    $('.patch-info').css('margin-top', margin+'px');
    $('.patch-info').css('transform', 'scale('+scale+')');
}

function updateProgress()
{
    // Showing or hiding the "well done"
    if (to_tag_user != 0 || training) {
        $('.tag-ok').show();
        $('.tag-zone').show();
        $('.tag-well-done').hide();
    } else {
        $('.tag-ok').hide();
        $('.tag-zone').hide();
        $('.tag-well-done').show();
    }

    if (!training) {
        // Showing or hiding the cancel button
        if (to_cancel) {
            $('.cancel-last').show();
        } else {
            $('.cancel-last').hide();
        }

        // Updating the "useful" info
        if (to_tag_user_no_consensus) {
            $('.contributions').text(to_tag_user_no_consensus+' useful remaining');
        } else {
            $('.contributions').text('improving quality');
        }

        // Updating the progress bars
        $('.tag-progress').show();
        var pct = (100*(to_tag-to_tag_user)/to_tag).toFixed(2);
        $('.tag-progress .progress-bar').css('width', pct+'%');
        $('.tag-progress span').text(pct+'%');

        $('.tag-team-progress').show();
        var pct_team = (100*(to_tag-to_tag_team)/to_tag).toFixed(2);
        $('.tag-team-progress .progress-bar').css('width', pct_team+'%');
        $('.tag-team-progress span').text(pct_team+'%');
    } else {
        updateTrainBar();
        $('.cancel-last').hide();
        $('.team-progress-row').hide();
        $('.progress-label').html('<b>Training progress:</b>');
        $('.contributions').text('training');
    }
}

function updateTrainBar()
{
    // Updating rhe progress bar using the train progress
    $('.tag-progress').show();
    var pct = train_progress*100;
    $('.tag-progress .progress-bar').css('width', pct.toFixed(2)+'%');
    $('.tag-progress span').text(pct.toFixed(2)+'%');
}

function updatePatches()
{
    updateProgress();

    // Getting new patches
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
            var to_save = patches;

            var data = {};
            for (var k in to_save) {
                data[to_save[k][0]] = to_save[k][2];
            }

            if (training) {
                // We are training, either we click ok with tags, or with the
                // review
                if (reviewing) {
                    // Getting new patches
                    updatePatches();
                    reviewing = false;
                } else {
                    // Reviewing the tags
                    $.post(review_url, data, function(json) {
                        if (json.trained) {
                            // The training is over
                            $('.tag-progress').hide();
                            $('.tag-ok').hide();
                            $('.tag-zone').hide();
                            $('.tag-well-done').show();
                        } else {
                            // Updating progress
                            train_progress = json.progress;
                            updateTrainBar();

                            // Showing green or red borders
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
                // We send our tags to the server
                $.post(send_url, data, function(json) {
                    // Updating patches
                    updatePatches();

                    // Updating statistics and progress bar
                    to_tag = json[0];
                    to_tag_user = json[1];
                    to_tag_user_no_consensus = json[2];
                    to_tag_team = json[3];
                    to_cancel = json[4];
                    updateProgress();
                });
            }
        }

        return false;
    });

    if (!training) {
        $('.tag-cancel').click(function() {
            $.post(cancel_url, {'tags': to_cancel}, function(json) {
                to_cancel = null;
                to_tag = json[0];
                to_tag_user = json[1];
                to_tag_user_no_consensus = json[2];
                to_tag_team = json[3];
                updateProgress();
            });

            return false;
        });
    }
});
