$("#download-xlsx").on("click", function() {
  let data = [];
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

  law_aoa_split = getLawAoa(last_section_idx, articleNums, bill_count, true);
  law_aoa = getLawAoa(last_section_idx, articleNums, bill_count, false);
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
      return getLawText(ele);
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
      return getLawReason(ele);
    });
    law_reason_aoa.unshift('立法理由');

    law_aoa.push(law_reason_aoa);

  }

  return law_aoa;
}

function getLawText(ele) {
  if (hasReason(ele)) {
    return $(ele).children('div').first().text().trim();
  }
  return $(ele).text().trim();
}

function getLawReason(ele) {
  if (hasReason(ele)) {
    return $(ele).find('div.help-body').text().trim();
  }
  return '';
}

//判斷 div 裡頭是否有包含 div.help-title(立法理由)
function hasReason(ele) {
  return $(ele).find('> div').length === 2;
}

function chunkArray(arr, n) {
  let result = [];
  for (let i = 0; i < arr.length; i += n) {
    result.push(arr.slice(i, i + n));
  }
  return result;
}
