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


  for (section_idx = 0; section_idx <= last_section_idx; section_idx++) {
    if (section_idx < last_section_idx) {
      divBetween = $('#section-' + section_idx).nextUntil('#section-' + (section_idx + 1));
    } else {
      divBetween = $('#section-' + section_idx).nextAll();
    }
    divBetween = divBetween.filter('.law-diff-content.law-diff-content-section');
    //TODO text to array
  }

});
