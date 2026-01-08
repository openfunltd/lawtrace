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
        right_lineno = -1;
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
                right_lineno = Math.max(0, left_lineno - 1);
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
    var total_count = Object.keys(diff_data.diff.versions).length - 1; // 去掉現行版本
    var diff_count = diff_data.choosed_version_ids.length - 1; // 去掉現行版本
    $('#selected-item').text("請選擇比較對象 (" + diff_count + " / " + total_count + ")");
    $('#selected-item').append($('<i class="bi icon bi-chevron-down"></i>'));
    for (var id of diff_data.choosed_version_ids) {
        if (id == '現行版本') {
            continue;
        }
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

    $('input#splitContent').change(function(){
        var val = $(this).prop('checked') ? 'section' : 'origin';
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

    $('input#splitContent').prop('checked', true).change();
    $('div.law-diff-content.original').each(gen_diff);
    $('input[name="diff-type"][value="only_add"]').prop('checked', true).change();

    //decide to hide horizontal scoll buttons or not
    lawDiffRow = $('.law-diff-row:not(.law-diff-header-row)').first();
    rowWidth = lawDiffRow.outerWidth(true);
    columnCnt = lawDiffRow.css('--col-count');
    lawCell = $('.law-diff-content.law-diff-content-section').first();
    cellWidth = lawCell.outerWidth(true);

    if (cellWidth * columnCnt <= rowWidth) {
      $('.compare-scroll-btns').first().hide();
    }

});

$('#btn-custom-compare').click(function(){
    url = '/law/compare?source=custom:' + diff_data.law_id;
    for (var version_id of diff_data.choosed_version_ids) {
        url += '&version[]=' + version_id;
    }
    url += '#set-compare-target';
    // open in new tab
    window.open(url, '_blank');
});

var law_versions = null;
$('.set-compare-target').on('modal-show', function() {
    // 從 diff_data.diff.versions 把他寫入 
    $('.version-list').html('');
    for (var version_id of diff_data.all_version_ids) {
      version_data = diff_data.diff.versions[version_id];
      version_list_dom = $($('#tmpl-version-list').html());
      version_str = version_data.title;
      if (version_data.subtitle && version_data.subtitle != '') {
          version_str += '｜' + version_data.subtitle;
      }
      if (version_data.article_numbers && version_data.article_numbers.length > 0) {
          if (version_data.article_numbers.length < 7) {
              version_str += "（第 " + version_data.article_numbers.join('、') + " 條）";
          } else {
              version_str += "（第 " + version_data.article_numbers.slice(0, 5).join('、') + " 等 " + version_data.article_numbers.length + " 條）";
          }
      }
      $('label.form-check-label', version_list_dom).text(version_str);
      $('input.form-check-input[name="versions[]"]', version_list_dom).prop('value', version_id);
      $('input.form-check-input[name="base"]', version_list_dom).prop('value', version_id);
      if (diff_data.choosed_version_ids.indexOf(version_id) != -1) {
          $('input.form-check-input[name="versions[]"]', version_list_dom).prop('checked', true);
      } else {
          $('input.form-check-input[name="versions[]"]', version_list_dom).prop('checked', false);
      }
      if (version_id == diff_data.base_version_id) {
          $('input.form-check-input[name="base"]', version_list_dom).prop('checked', true);
      }
      $('.version-list').append(version_list_dom);
    }
    check_term_by_date = function(date) {
      range = {
        11: [ 20240201, 20280131 ],
        10: [ 20200201, 20240131 ],
        9: [ 20160201, 20200131 ],
        8: [ 20120201, 20160131 ],
        7: [ 20080201, 20120131 ], // 任期改為4年
        6: [ 20050201, 20080131 ],
        5: [ 20020201, 20050131 ],
        4: [ 19990201, 20020131 ],
        3: [ 19960201, 19990131 ],
        2: [ 19930201, 19960131 ],
        1: [ 19480201, 19930131 ], // 萬年國會
      }
      // 如果是 string ，去掉 - 轉成 int
      if (typeof date === 'string') {
          date = parseInt(date.replace(/-/g, ''));
      }
      // 檢查 date(int YYYYMMDD) 在哪個 range 裡面
      for (var term in range) {
        if (date >= range[term][0] && date <= range[term][1]) {
          return term;
        }
      }
      return 1;
    };
    $.when(
            $.get(ly_api_base + '/stat'),
            $.get(ly_api_base + '/law/' + diff_data.law_id + '/versions')
          ).done(function(stats_data, lawversions_data){
              // 幫每個 version 加上 term
              term_versions = [];
              law_versions = lawversions_data[0].lawversions;
              for (var i = 0; i < law_versions.length; i++) {
                  term = check_term_by_date(law_versions[i]['日期']);
                  law_versions[i].term = term;
                  if ('undefined' === typeof(term_versions[term])) {
                      term_versions[term] = [];
                  }
                  term_versions[term].unshift(law_versions[i]);
              }

              current_term = stats_data[0].legislator.terms[0].term;
              $('#version-select-list').html('');
              for (term = current_term; term >= law_versions[0].term; term--) {
                term_str = "第 " + term + " 屆";
                if (term == current_term) {
                    term_str += "（目前屆期）";
                }
                $('<div></div>')
                   .addClass('dropdown-item disabled group-label')
                   .text(term_str)
                   .appendTo('#version-select-list');
                if ('undefined' === typeof(term_versions[term])) {
                    versions = [];
                } else { 
                    versions = term_versions[term];
                }
                for (var version of versions) {
                  date_term = version['日期'].split('-');
                  version['日期'] = (parseInt(date_term[0]) - 1911) + '/' + date_term[1] + '/' + date_term[2];
                  version_str = version['日期'] + '｜' + version['動作'];

                  version_dom = $('<span></span>')
                      .addClass('dropdown-item')
                      .data('version_data', version)
                      .data('version_id', version['版本編號'])
                      .text(version_str)
                      .appendTo('#version-select-list');
                }

                if (term == current_term) {
                    more_str = `第 ${term} 屆待審議案`;
                } else {
                    more_str = `第 ${term} 屆過期議案`;
                }
                version_dom = $('<span></span>')
                    .addClass('dropdown-item')
                    .data('version_id', 'more-' + term)
                    .text(more_str)
                    .appendTo('#version-select-list');
              }
    });

    $('#version-choose-list').on('click', '.dropdown-item', function(e){
        var version_id = $(this).data('bill-no');
        $.get("/law/billdata?billno=" + version_id).done(function(version_data){
            version_list_dom = $($('#tmpl-version-list').html());
            version_str = version_data.title;
            if (version_data.subtitle && version_data.subtitle != '') {
                version_str += '｜' + version_data.subtitle;
            }
            if (version_data.article_numbers.length < 7) {
                version_str += "（第 " + version_data.article_numbers.join('、') + " 條）";
            } else {
                version_str += "（第 " + version_data.article_numbers.slice(0, 5).join('、') + " 等 " + version_data.article_numbers.length + " 條）";
            }
            $('label.form-check-label', version_list_dom).text("[新增] " + version_str);
            $('input.form-check-input[name="versions[]"]', version_list_dom).prop('value', version_id);
            $('input.form-check-input[name="base"]', version_list_dom).prop('value', version_id);
            $('input.form-check-input[name="versions[]"]', version_list_dom).prop('checked', true);
            if (version_id == diff_data.base_version_id) {
                $('input.form-check-input[name="base"]', version_list_dom).prop('checked', true);
            }
            $('.version-list').append(version_list_dom);
            $('#version-select-list').click();
        });
    });

    $('#version-select-list').on('click', '.dropdown-item', function(e){
        var version_id = $(this).data('version_id');
        $('#version-choose-list').html('');
        $('#version-select-selected .text').text($(this).text());
        if (version_id.toString().startsWith('more-')) {
            term = version_id.toString().split('-')[1];
            $.get(ly_api_base + '/law/' + diff_data.law_id + '/progress?屆=' + term).done(function(progress_data){
                for (var log of progress_data['歷程']) {
                    for (var bill of log.bill_log) {
                        if ('undefined' === typeof(bill.關係文書.billNo)) {
                            continue;
                        }
                        if ('undefined' !== typeof(diff_data.diff.versions[bill.關係文書.版本編號])) {
                            continue;
                        }
                        date_term = bill.會議日期.split('-');
                        bill.會議日期 = (parseInt(date_term[0]) - 1911) + '/' + date_term[1] + '/' + date_term[2];
                        version_str = bill.主提案 + '｜' + bill.會議日期 + ' 提案版本';
                        version_dom = $('<span></span>')
                            .addClass('dropdown-item')
                            .data('bill-no', bill.關係文書.billNo)
                            .text(version_str)
                            .appendTo('#version-choose-list');
                    }
                }
            });
        } else {
            var version_data = $(this).data('version_data');
            for (var log of version_data['歷程']) {
                if ('undefined' === typeof(log.關係文書)) {
                    continue;
                }
                if ('undefined' === typeof(log.關係文書[0])) {
                    continue;
                }
                if ('undefined' === typeof(log.關係文書[0].billNo)) {
                    continue;
                }
                if ('undefined' !== typeof(diff_data.diff.versions[log.關係文書[0].版本編號])) {
                    continue;
                }
                date_term = log.會議日期.split('-');
                log.會議日期 = (parseInt(date_term[0]) - 1911) + '/' + date_term[1] + '/' + date_term[2];
                version_str = log.主提案 + '｜' + log.會議日期 + ' 提案版本';
                version_dom = $('<span></span>')
                    .addClass('dropdown-item')
                    .data('bill-no', log.關係文書[0].billNo)
                    .text(version_str)
                    .appendTo('#version-choose-list');
            }
        }
        $('#version-select-selected').click();
    });
});

$('#btn-update-compare').click(function(e){
    e.preventDefault();

    // update choosed_version_ids from .version-list input[name="versions[]"]
    diff_data.choosed_version_ids = [];
    $('.version-list input[name="versions[]"]:checked').each(function(){
        diff_data.choosed_version_ids.push($(this).val());
    });
    base_version = $('.version-list input[name="base"]:checked').val();
    url = "/law/compare?source=" + encodeURIComponent(diff_data.source);
    for (var version_id of diff_data.choosed_version_ids) {
        url += '&version[]=' + version_id;
    }
    if (base_version != diff_data.choosed_version_ids[0]) {
        url += '&base_version=' + base_version;
    }
    document.location = url;
});

$(function(){
    if (document.location.hash == '#set-compare-target') {
        $('.set-compare-target').click();
        document.location.hash = '';
    }
});
