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
    first_td_text = div_dom.text();
    first_td_text = filter_empty_line(first_td_text);

    diff_table = [];
    diff_table[0] = [];
    for (var line of first_td_text.split('\n')) {
        diff_table[0].push({
            value: line,
        });
    }
    // 一一比較所有版本
    version_idx = 0;
    compare_td_dom = div_dom;
    while (true) {
        compare_td_dom = compare_td_dom.next('div.law-diff-content');
        version_idx ++;
        if (div_dom.data('rule-no') != compare_td_dom.data('rule-no')) {
            break;
        }
        var compare_td_text = compare_td_dom.text();
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
            right_lineno ++;
            if (right_lineno > left_lineno) {
                right_lineno = left_lineno;
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

$(function(){
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
            $(this).html(gen_diff_html(origin_text, compare_text, val));
        });
    });

    $('input[name="content-type"][value="section"]').prop('checked', true).change();
    $('div.law-diff-content.original').each(gen_diff);
    $('input[name="diff-type"][value="only_add"]').prop('checked', true).change();
});


