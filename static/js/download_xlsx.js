$("#download-xlsx").on("click", function() {
  let data = [];
  let data_split = [];
  let titles = ['版本名稱'];
  let proposal_dates = ['提案日期'];
  let ppg_links = ['原始資料'];

  $(".law-diff-head").each(function(index, element) {
    title_div = $(element).find('.title');

    //get bill title
    title_str = title_div.clone().children().remove().end().text().trim();
    titles.push(title_str);

    //get proposal date
    proposal_date = title_div.find("small").text().trim();
    proposal_dates.push(proposal_date);

    //get ppg(立法院議事暨公報資訊網) link
    action_div = $(element).find('.action');
    let links = action_div.find('a');
    let ppg_link = '';
    for (let i = 0; i < links.length; i++) {
      const a_tag = $(links[i]);
      if (a_tag.text().trim() == '查看原始資料') {
        ppg_link = a_tag.attr('href');
      }
    }
    ppg_links.push(ppg_link);
  });

  data.push(titles);
  data.push(proposal_dates);
  data.push(ppg_links);

  data_split.push(titles);
  data_split.push(proposal_dates);
  data_split.push(ppg_links);

  //get max index of id='section-i' (選擇章節的項目index)
  const lastSection = $(".law-diff-row").eq(1).children("div[id^='section-']").last();
  lastIdParts = lastSection.attr('id').split('-');
  last_section_idx = parseInt(lastIdParts[1], 10);

  //get number of bills
  bill_count = titles.length - 1;

  //get article number(條號)
  const articleNumDivs = $("div[id^='section-']").toArray();
  articleNums = articleNumDivs.map(function(ele) {
    return $(ele).text().trim();
  });

  law_aoa = getLawAoa(last_section_idx, articleNums, bill_count, false);
  data = data.concat(law_aoa);

  law_aoa_split = getLawAoa(last_section_idx, articleNums, bill_count, true);
  data_split = data_split.concat(law_aoa_split);

  //metadata
  const metadata_aoa = getMetadataAoa();

  //ppg_links
  const ppg_link_aoa = getPpgLinkAoa(titles, proposal_dates, ppg_links);

  //build xlsx
  //create worksheets
  const worksheet1 = XLSX.utils.aoa_to_sheet(metadata_aoa);
  const worksheet2 = XLSX.utils.aoa_to_sheet(ppg_link_aoa);
  const worksheet3 = XLSX.utils.aoa_to_sheet(data);
  const worksheet4 = XLSX.utils.aoa_to_sheet(data_split);

  //create workbook
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet1, "詮釋資料");
  XLSX.utils.book_append_sheet(workbook, worksheet2, "提案原始資料連結");
  XLSX.utils.book_append_sheet(workbook, worksheet3, "對照表");
  XLSX.utils.book_append_sheet(workbook, worksheet4, "對照表（分句）");

  //get law_name, source_str and timestamp for excel file name
  const law_name = $('li.breadcrumb-item').eq(1).find('a').text().trim();
  const source_str = buildSourceStr();
  const time = getTimestamp();

  //downlaod excel file
  XLSX.writeFile(workbook, `${law_name}-${source_str}-${time}.xlsx`);
});

function getLawAoa(last_section_idx, articleNums, bill_count, split) {
  law_aoa = [];
  classname = (split) ? 'law-diff-content-section' : 'law-diff-content-origin';

  for (section_idx = 0; section_idx <= last_section_idx; section_idx++) {
    if (section_idx < last_section_idx) {
      divBetween = $('#section-' + section_idx).nextUntil('#section-' + (section_idx + 1));
    } else {
      divBetween = $('#section-' + section_idx).nextAll();
    }

    divBetween = divBetween.filter('.law-diff-content.' + classname).toArray();
    law_content = divBetween.map(function(ele) {
      return getLawText(ele, split);
    });

    //法律內文 law_content
    law_content_aoa = chunkArray(law_content, bill_count);

    //first column: article number(條號) or 空白
    for (let i = 0; i < law_content_aoa.length; i++) {
      prepend = '';
      if (i == 0) {
        prepend = articleNums[section_idx];
      }
      law_content_aoa[i].unshift(prepend);
    }

    law_aoa = law_aoa.concat(law_content_aoa);

    //立法理由 law_reason
    divLastRow = divBetween.slice(-1 * bill_count);
    law_reason_aoa = divLastRow.map(function (ele) {
      return getLawReason(ele, split);
    });
    law_reason_aoa.unshift('立法理由');

    law_aoa.push(law_reason_aoa);

  }

  return law_aoa;
}

function getLawText(ele, split) {
  if (hasReason(ele, split)) {
    if (split) {
      return $(ele).children('div').first().text().trim();
    }
    return $(ele).children('span').first().text().trim();
  }
  return $(ele).text().trim();
}

function getLawReason(ele, split) {
  if (hasReason(ele, split)) {
    return $(ele).find('div.help-body').text().trim();
  }
  return '';
}

//判斷 div 裡頭是否有包含 div.help-title(立法理由)
function hasReason(ele, split) {
  if (split) {
    return $(ele).find('> div').length === 2;
  }
  return $(ele).find('> div').length === 1;
}

function buildSourceStr() {
  const source_type_str = $('li.breadcrumb-item').eq(2).text().trim();
  let str = source_type_str;
  if (source_type_str == '三讀版本') {
    str = str + '-' + $('li.breadcrumb-item').eq(3).text().trim().replace(/\s+/g, '');
  } else if (source_type_str == '審查報告') {
    str = str + '-' + $('div.review-date').eq(0).text().trim().replace(/\s+/g, '').split('：')[1];
    str = str + '-' + $('div.review-committee').first().text().trim().split('：')[1];
  } else if (source_type_str == '法律議案') {
    str = str + '-' + $('div.review-date').eq(0).text().trim().replace(/\s+/g, '').split('：')[1];
    str = str + '-' + $('li.breadcrumb-item').eq(3).text().trim();
  } else if (source_type_str == '委員會審查') {
    str = str + '-' + $('div.review-date').eq(0).text().trim().replace(/\s+/g, '').split('：')[1];
    str = str + '-' + $('div.review-committee').first().text().trim().split('：')[1];
  } else {
    str = str + '-unknown_source';
  }
  return str;
}

function getMetadataAoa() {
  let metadata_aoa = [];
  const source_type_str = $('li.breadcrumb-item').eq(2).text().trim();
  metadata_aoa.push(['Lawtrace 網址', window.location.href]);
  metadata_aoa.push(['檔案下載時間', getTimestampHumanReadable()]);
  metadata_aoa.push(['法律名稱', $('li.breadcrumb-item').eq(1).find('a').text().trim()]);
  metadata_aoa.push(['比較來源', source_type_str]);

  return metadata_aoa;
}

function getPpgLinkAoa(titles, proposal_dates, ppg_links) {
  //transpose 轉置
  let ppg_link_aoa = [];
  for (let i = 0; i < titles.length; i++) {
    ppg_link_aoa.push([titles[i], proposal_dates[i], ppg_links[i]])
  }

  return ppg_link_aoa;
}

function chunkArray(arr, n) {
  let result = [];
  for (let i = 0; i < arr.length; i += n) {
    result.push(arr.slice(i, i + n));
  }
  return result;
}

function getTimestamp() {
  const now = new Date();
  return (
    now.getFullYear().toString() +
    String(now.getMonth() + 1).padStart(2, '0') +
    String(now.getDate()).padStart(2, '0') +
    String(now.getHours()).padStart(2, '0') +
    String(now.getMinutes()).padStart(2, '0') +
    String(now.getSeconds()).padStart(2, '0')
  );
}

function getTimestampHumanReadable() {
  const now = new Date();
  return (
    now.getFullYear().toString() + '-' +
    String(now.getMonth() + 1).padStart(2, '0') + '-' +
    String(now.getDate()).padStart(2, '0') + ' ' +
    String(now.getHours()).padStart(2, '0') + ':' +
    String(now.getMinutes()).padStart(2, '0') + ':' +
    String(now.getSeconds()).padStart(2, '0')
  );
}
