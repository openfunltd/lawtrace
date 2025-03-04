var gen_diff = function() {
    const div_dom = $(this);

    const diff_options = {
        ignoreWhitespace: true,
    };

    var filter_empty_line = function(text) {
        text = $.trim(text);
        text = text.split("\n").filter(function(value, index) {
            return $.trim(value) != '';
        }).join("\n");
        text = text.replace(/　/g, '');
        return text;
    };

    line_index = [];
    first_td_text = div_dom.find('.law-diff-content-text').text();
    first_td_text = filter_empty_line(first_td_text);

    diff_table = [];
    diff_table[0] = [];
    for (var line of first_td_text.split('\n')) {
        diff_table[0].push({
            value: line,
        });
    }
    // 儲存立法理由的 dom
    var reason_doms = [];
    // 一一比較所有版本
    version_idx = 0;
    compare_td_dom = div_dom;
    while (true) {
        compare_td_dom = compare_td_dom.next('div.law-diff-content');
        version_idx ++;
        if (div_dom.data('rule-no') != compare_td_dom.data('rule-no')) {
            break;
        }
        if (compare_td_dom.find('.card-help').length) {
            reason_doms[version_idx] = compare_td_dom.find('.card-help').eq(0);
        }
        var compare_td_text = compare_td_dom.find('.law-diff-content-text').text();
        compare_td_text = filter_empty_line(compare_td_text);
        var diffs = Diff.diffChars(first_td_text, compare_td_text, diff_options);

        diff_table[version_idx] = [];
        // 把 diffs 轉成一行一行
        new_diffs = [];
        left_text = '';
        right_text = '';
        added = removed = same = 0;
        for (var diff of diffs) {
            if (diff.added) {
                added += 1;
            } else if (diff.removed) {
                removed += 1;
            } else {
                same += 1;
            }

            lines = diff.value.split('\n');
            while (lines.length > 1) {
                var line = lines.shift();
                if (diff.added) {
                    new_diffs.push({
                        added: true,
                        removed: false,
                        value: right_text + line,
                    });
                    right_text = '';
                } else if (diff.removed) {
                    new_diffs.push({
                        added: false,
                        removed: true,
                        value: left_text + line,
                    });
                    left_text = '';
                } else {
                    left_text += line;
                    right_text += line;
                    if (left_text == right_text) {
                        new_diffs.push({
                            added: false,
                            removed: false,
                            value: left_text,
                        });
                        left_text = '';
                        right_text = '';
                    } else {
                        new_diffs.push({
                            added: false,
                            removed: true,
                            value: left_text,
                        });
                        left_text = '';
                        new_diffs.push({
                            added: true,
                            removed: false,
                            value: right_text,
                        });
                        right_text = '';
                    }
                }
            }
            line = lines.shift();
            if (diff.added) {
                right_text += line;
            } else if (diff.removed) {
                left_text += line;
            } else {
                left_text += line;
                right_text += line;
            }
        }
        if (left_text != '' && left_text == right_text) {
            new_diffs.push({
                added: false,
                removed: false,
                value: left_text,
            });
        } else if (left_text != '' || right_text != '') {
            new_diffs.push({
                added: false,
                removed: true,
                value: left_text,
            });
            new_diffs.push({
                added: true,
                removed: false,
                value: right_text,
            });
        } else if (left_text != '') {
            new_diffs.push({
                added: false,
                removed: true,
                value: left_text,
            });
        } else if (right_text != '') {
            new_diffs.push({
                added: true,
                removed: false,
                value: right_text,
            });
        }

        // 完全沒有相同的，那就直接新資料佔一整行
        if (same == 0) {
            diff_table[version_idx][0] = {
                value: compare_td_text,
                type: 'over',
            };
            continue;
        }

        // 計算相同和不同的
        left_lineno = 0;
        right_lineno = 0;
        for (var diff of new_diffs) {
            // same
            if (!diff.added && !diff.removed) {
                diff_table[version_idx][left_lineno] = {
                    value: diff.value,
                    type: 'same',
                };
                left_lineno ++;
                right_lineno = left_lineno;
                continue;
            }

            if (diff.removed) {
                if ('undefined' === typeof(diff_table[version_idx][right_lineno])) {
                    diff_table[version_idx][right_lineno] = {
                        value: '(移除)',
                        type: 'removed',
                    };
                }   
                left_lineno ++;
                continue;
            }

            // added
            right_lineno ++;
            if (right_lineno >= left_lineno) {
                right_lineno = left_lineno - 1;
            }
            if ('undefined' !== typeof diff_table[version_idx][right_lineno]) {
                if (diff_table[version_idx][right_lineno].type == 'removed') {
                    diff_table[version_idx][right_lineno] = {
                        value: diff.value,
                        type: 'changed',
                    };
                } else {
                    diff_table[version_idx][right_lineno].value += "\n" + diff.value;
                    if (diff_table[version_idx][right_lineno].type == 'same') {
                        diff_table[version_idx][right_lineno].type = 'changed';
                    }
                }
            } else {
                    diff_table[version_idx][right_lineno] = {
                        value: diff.value,
                        type: 'changed',
                    };
            }
        }
    }
    // 新增新的 tr ，依分段來作
    for (var lineno = 0; lineno < diff_table[0].length; lineno ++) {
        origin_td_dom = $('<div></div>')
            .text(diff_table[0][lineno].value)
            .data('value', diff_table[0][lineno].value)
            .addClass('original')
            .addClass('law-diff-content')
            .addClass('law-diff-content-section')
        ;
        origin_td_dom.insertBefore($(this));

        for (var version_idx = 1; version_idx < diff_table.length; version_idx ++) {
            cell_data = diff_table[version_idx][lineno];
            if ('undefined' === typeof cell_data) {
                cell_data = {
                    value: '',
                    type: 'over',
                };
            }
            td_dom = $('<div></div>')
                .text(cell_data.value)
                .data('value', cell_data.value)
                .data('origin_td', origin_td_dom)
                .addClass('law-diff-content')
                .addClass('law-diff-content-section')
                ;
            // 如果是最後一行，要把立法理由也放進去
            if (lineno == diff_table[0].length - 1) {
                if ('undefined' !== typeof(reason_doms[version_idx])) {
                    td_dom.append(reason_doms[version_idx].clone());
                }
            }
            if (cell_data.type == 'same') {
                td_dom.addClass('no-modification');
            }
            td_dom.insertBefore($(this));
        }
    }
};

var gen_diff_html = function(one, other, diff_type) {
    // diff_type: none (原文), only_add (只顯示新增), update (顯示新增和刪除)
    const diff = Diff.diffChars(one, other);
    dom = $('<div></div>');

    diff.forEach((part) => {
        // green for additions, red for deletions
        // grey for common parts
        if (part.added && diff_type != 'none') {
            dom.append($('<span class="add"></span>').text(part.value));
        } else if (part.removed) {
            if (diff_type == 'update') {
                dom.append($('<span class="remove"></span>').text(part.value));
            }
        } else {
            dom.append(part.value);
        }
    });
    return dom;
};

$('#compare-list').on('click', '.delete', function(e){
    e.preventDefault();
    var version_id = $(this).closest('.tag').data('version_id');
    diff_data.choosed_version_ids = diff_data.choosed_version_ids.filter(function(value, index, arr){
        return value != version_id;
    });
    update_compare_list();
});

$('[name="choosed_version_ids[]"]').change(function(){
    diff_data.choosed_version_ids = [];
    $('[name="choosed_version_ids[]"]:checked').each(function(){
        diff_data.choosed_version_ids.push($(this).val());
    });
    update_compare_list();
});

$('#btn-submit').click(function(){
    var get_params = [];
    get_params.push('source=' + diff_data.source);
    for (var version_id of diff_data.choosed_version_ids) {
        get_params.push('version[]=' + version_id);
    }
    window.location.href = '/law/compare?' + get_params.join('&');
    
});

var update_compare_list = function() {
    $('#compare-list').html('');
    for (var id of diff_data.choosed_version_ids) {
        const version_data = diff_data.diff.versions[id];
        var tag_dom = $('<span class="tag"></span>');
        tag_dom.data('version_id', id);
        tag_dom.text(version_data.title + '｜' + version_data.subtitle);
        tag_dom.append('<a href="#" class="delete"><i class="bi bi-x-lg"></i></a>');
        $('#compare-list').append(tag_dom);

        $('[name="choosed_version_ids[]"][value="' + id + '"]').prop('checked', true);
    }

    // uncheck other choosed_version_ids
    $('[name="choosed_version_ids[]"]').each(function(){
        if (diff_data.choosed_version_ids.indexOf($(this).val()) == -1) {
            $(this).prop('checked', false);
        }
    });
};

$(function(){
    update_compare_list();

    $('#showCategory').change(function(){
        var checked = $(this).prop('checked');
        if (checked) {
            $('.law-sections').show();
        } else {
            $('.law-sections').hide();
        }
    });
    $('#expandLawHelp').change(function(){
        var checked = $(this).prop('checked');
        $('.card-help').each(function(){
            if (checked) {
                $('.help-body', this).show();
                $('i', this).removeClass('bi-chevron-down').addClass('bi-chevron-up');
            } else {
                $('.help-body', this).hide();
                $('i', this).removeClass('bi-chevron-up').addClass('bi-chevron-down');
            }
        });
    });

    $('input[name="content-type"]').change(function(){
        var val = $(this).val();
        $('div.law-diff-content-origin').hide();
        $('div.law-diff-content-section').hide();
        $('div.law-diff-content-' + val).show();
    });

    $('input[name="diff-type"]').change(function(){
        var val = $(this).val();
        $('.law-diff-content-section').each(function(){
            if ($(this).is('.original')) {
                return;
            }
            var origin_text = $(this).data('origin_td').data('value');
            var compare_text = $(this).data('value');
            if (compare_text == '') {
                return;
            }
            // 補上立法理由
            var card_help_dom = null;
            if ($(this).find('.card-help').length) {
                card_help_dom = $(this).find('.card-help').eq(0).clone();
            }
            $(this).html(gen_diff_html(origin_text, compare_text, val));
            if (card_help_dom) {
                $(this).append(card_help_dom);
            }
            
        });
    });

    $('input[name="content-type"][value="section"]').prop('checked', true).change();
    $('div.law-diff-content.original').each(gen_diff);
    $('input[name="diff-type"][value="only_add"]').prop('checked', true).change();
});


