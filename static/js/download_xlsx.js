$("#download-xlsx").on("click", async function() {
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

  //ppg_links
  const ppg_link_aoa = getPpgLinkAoa(titles, proposal_dates, ppg_links);

  //metadata
  const metadata_aoa = getMetadataAoa();

  //build xlsx using ExcelJS for rich text diff formatting
  let ExcelJS;
  try {
    ExcelJS = await loadExcelJS();
  } catch (e) {
    alert('無法載入 ExcelJS，請確認網路連線後再試。');
    return;
  }

  const workbook = new ExcelJS.Workbook();

  function addSheetFromAoa(name, aoa) {
    const ws = workbook.addWorksheet(name);
    ws.views = [{ state: 'frozen', xSplit: 2, ySplit: 1, topLeftCell: 'C2', activeCell: 'C2' }];

    for (let rowIdx = 0; rowIdx < aoa.length; rowIdx++) {
      const rowValues = aoa[rowIdx];
      const row = ws.addRow(new Array(rowValues.length).fill(null));
      rowValues.forEach((v, i) => {
        const cell = row.getCell(i + 1);
        cell.value = v;
        cell.alignment = { wrapText: true, vertical: 'top' };
      });
      // First 3 rows (版本名稱 / 提案日期 / 原始資料) keep default height
      if (rowIdx >= 3) {
        row.height = 80;
      }
    }

    const colCount = aoa.length > 0 ? Math.max(...aoa.map(r => r.length)) : 0;
    if (colCount > 0) {
      ws.getColumn(1).width = 10;
      for (let c = 2; c <= colCount; c++) {
        ws.getColumn(c).width = 45;
      }
    }
  }

  addSheetFromAoa('對照表', data);
  addSheetFromAoa('對照表（分句）', data_split);
  addSheetFromAoa('提案原始資料連結', ppg_link_aoa);
  addSheetFromAoa('詮釋資料', metadata_aoa);

  //get law_name, source_str for excel file name
  const law_name = $('li.breadcrumb-item').eq(1).find('a').text().trim();
  const source_str = buildSourceStr();

  //download excel file
  const buffer = await workbook.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${law_name}-${source_str}.xlsx`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
});

function loadExcelJS() {
  return new Promise((resolve, reject) => {
    if (window.ExcelJS) { resolve(window.ExcelJS); return; }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js';
    script.onload = () => resolve(window.ExcelJS);
    script.onerror = () => reject(new Error('Failed to load ExcelJS'));
    document.head.appendChild(script);
  });
}

// Walk DOM contents and collect text runs with diff type (add/remove/null)
function parseDiffContent($container) {
  const runs = [];
  $container.contents().each(function() {
    if (this.nodeType === 3) {
      const text = this.textContent;
      if (text) runs.push({ text, type: null });
    } else if (this.nodeType === 1) {
      const $el = $(this);
      if ($el.is('br')) return;
      if ($el.hasClass('add')) {
        const text = $el.text();
        if (text) runs.push({ text, type: 'add' });
      } else if ($el.hasClass('remove')) {
        const text = $el.text();
        if (text) runs.push({ text, type: 'remove' });
      } else {
        parseDiffContent($el).forEach(r => runs.push(r));
      }
    }
  });
  return runs;
}

// Convert diff runs to ExcelJS cell value:
// plain string when no diff markup, richText object when diff exists
function runsToValue(runs) {
  const nonEmpty = runs.filter(r => r.text !== '');
  if (nonEmpty.length === 0) return '';
  if (!nonEmpty.some(r => r.type)) {
    return nonEmpty.map(r => r.text).join('').trim();
  }
  const richText = nonEmpty.map(r => {
    if (r.type === 'add') {
      return { text: r.text, font: { color: { argb: 'FF006600' }, underline: true } };
    }
    if (r.type === 'remove') {
      return { text: r.text, font: { color: { argb: 'FFCC0000' }, strike: true } };
    }
    return { text: r.text };
  });
  return { richText };
}

function getLawAoa(last_section_idx, articleNums, bill_count, split) {
  const law_aoa = [];

  for (let section_idx = 0; section_idx <= last_section_idx; section_idx++) {
    let divBetween;
    if (section_idx < last_section_idx) {
      divBetween = $('#section-' + section_idx).nextUntil('#section-' + (section_idx + 1));
    } else {
      divBetween = $('#section-' + section_idx).nextAll();
    }

    // Always read section divs — they have diff markup (span.add / span.remove) applied by diff.js.
    // Origin divs are server-rendered plain text and never receive diff markup.
    // Layout in DOM: [line0_v0, line0_v1, ..., line1_v0, line1_v1, ...] repeating bill_count per line.
    const sectionDivs = divBetween.filter('.law-diff-content.law-diff-content-section').toArray();

    if (split) {
      //分句: one row per sentence, one cell per version
      const law_content = sectionDivs.map(ele => getLawText(ele, true));
      const law_content_aoa = chunkArray(law_content, bill_count);
      for (let i = 0; i < law_content_aoa.length; i++) {
        law_content_aoa[i].unshift(i === 0 ? articleNums[section_idx] : '');
      }
      law_aoa.push(...law_content_aoa);
    } else {
      //對照表: one row per article — aggregate all sentences for each version into one cell
      const versionCells = [];
      for (let vi = 0; vi < bill_count; vi++) {
        const versionDivs = sectionDivs.filter((_, i) => i % bill_count === vi);
        const allRuns = [];
        versionDivs.forEach((ele, lineIdx) => {
          const $content = hasReason(ele, true) ? $(ele).children('div').first() : $(ele);
          const runs = parseDiffContent($content);
          if (lineIdx > 0 && allRuns.length > 0) {
            allRuns.push({ text: '\n', type: null });
          }
          runs.forEach(r => allRuns.push(r));
        });
        versionCells.push(runsToValue(allRuns));
      }
      law_aoa.push([articleNums[section_idx], ...versionCells]);
    }

    //立法理由: last bill_count section divs carry the card-help for each version
    const divLastRow = sectionDivs.slice(-bill_count);
    const law_reason_aoa = divLastRow.map(ele => getLawReason(ele, true));
    law_reason_aoa.unshift('立法理由');
    law_aoa.push(law_reason_aoa);
  }

  return law_aoa;
}

function getLawText(ele, split) {
  let $content;
  if (hasReason(ele, split)) {
    $content = split ? $(ele).children('div').first() : $(ele).children('span').first();
  } else {
    $content = $(ele);
  }
  return runsToValue(parseDiffContent($content));
}

function getLawReason(ele, split) {
  if (hasReason(ele, split)) {
    return runsToValue(parseDiffContent($(ele).find('div.help-body')));
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

// Read a labelled field from the .metadata div rendered by law_hero.php
// e.g. getMetaText('審查委員會') returns the text after '審查委員會：'
function getMetaText(label) {
  let result = '';
  $('.metadata > div').each(function() {
    const text = $(this).text().trim();
    const prefix = label + '：';
    if (text.startsWith(prefix)) {
      result = text.substring(prefix.length).trim();
      return false;
    }
  });
  return result;
}

// Extract a list of names from a labelled div (handles img+text nodes for 提案人/連署人)
function getMetaNames(label) {
  let names = [];
  $('.metadata > div').each(function() {
    const text = $(this).text().trim();
    if (!text.startsWith(label + '：')) return;
    $(this).contents().each(function() {
      if (this.nodeType === 3) {
        const t = this.textContent.replace(/ /g, '').trim();
        if (t) names.push(t);
      }
    });
    return false;
  });
  return names;
}

function buildSourceStr() {
  const src = diff_data.source;
  const prefix = src.split(':')[0];

  if (prefix === 'version') {
    return '三讀版本-' + getMetaText('三讀日期').replace(/\s+/g, '');
  } else if (prefix === 'bill') {
    const reviewDate = getMetaText('審查會發文日期');
    if (reviewDate !== '') {
      return '審查報告-' + reviewDate.replace(/\s+/g, '') + '-' + getMetaText('審查委員會');
    }
    return '法律議案-' + getMetaText('提案日期').replace(/\s+/g, '');
  } else if (prefix === 'meet') {
    return '委員會審查-' + getMetaText('審查會議日期').replace(/\s+/g, '') + '-' + getMetaText('審查委員會');
  } else if (prefix === 'join-policy') {
    return '部預告版-' + getMetaText('發布日期').replace(/\s+/g, '');
  } else if (prefix === 'custom') {
    return '自訂比較';
  }
  return prefix;
}

function getMetadataAoa() {
  let metadata_aoa = [];
  const src = diff_data.source;
  const prefix = src.split(':')[0];

  metadata_aoa.push(['Lawtrace 網址', window.location.href]);
  metadata_aoa.push(['檔案下載時間', getTimestamp()]);
  metadata_aoa.push(['法律名稱', $('li.breadcrumb-item').eq(1).find('a').text().trim()]);
  metadata_aoa.push(['比較來源', buildSourceStr()]);

  if (prefix === 'version') {
    metadata_aoa.push(['三讀日期', getMetaText('三讀日期')]);
  } else if (prefix === 'bill') {
    const reviewDate = getMetaText('審查會發文日期');
    if (reviewDate !== '') {
      metadata_aoa.push(['審查委員會', getMetaText('審查委員會')]);
      metadata_aoa.push(['審查會發文日期', reviewDate]);
      metadata_aoa.push(['議案狀態', getMetaText('議案狀態')]);
    } else {
      const proposers = getMetaNames('提案人');
      const cosigners = getMetaNames('連署人');
      metadata_aoa.push(['提案人'].concat(proposers));
      metadata_aoa.push(['連署人'].concat(cosigners));
      metadata_aoa.push(['提案日期', getMetaText('提案日期')]);
      metadata_aoa.push(['議案狀態', getMetaText('議案狀態')]);
      const 案由 = getMetaText('案由');
      if (案由) metadata_aoa.push(['案由', 案由]);
    }
  } else if (prefix === 'meet') {
    metadata_aoa.push(['審查委員會', getMetaText('審查委員會')]);
    metadata_aoa.push(['審查會議日期', getMetaText('審查會議日期')]);
    metadata_aoa.push(['召委', getMetaText('召委')]);
  } else if (prefix === 'join-policy') {
    metadata_aoa.push(['主協辦單位', getMetaText('主協辦單位')]);
    metadata_aoa.push(['發布日期', getMetaText('發布日期')]);
  }

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
    now.getFullYear().toString() + '-' +
    String(now.getMonth() + 1).padStart(2, '0') + '-' +
    String(now.getDate()).padStart(2, '0') + ' ' +
    String(now.getHours()).padStart(2, '0') + ':' +
    String(now.getMinutes()).padStart(2, '0') + ':' +
    String(now.getSeconds()).padStart(2, '0')
  );
}
