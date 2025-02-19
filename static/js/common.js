function init () {
  const closeIcon = 'bi-chevron-down';
  const openIcon = 'bi-chevron-up';
  const hide = 'd-none';
  const block = 'd-block';
  const show = 'show';

  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

  // 顯示範圍區塊下拉選單
  $(document).on('click', '.dropdown-select .selected-item', (event) => {
    const select = $(event.currentTarget.parentNode);
    const icon = select.find('.icon');
    const menu = select.find('.select-list');

    if (icon.hasClass(closeIcon)) {
      icon.removeClass(closeIcon).addClass(openIcon);
      menu.addClass(show);
    } else {
      icon.removeClass(openIcon).addClass(closeIcon);
      menu.removeClass(show);
    }
  });

  // 樹狀選單
  $(document).on('click', '.side-menu .menu-head i', (event) => {
    const menuItem = $(event.currentTarget.parentNode.parentNode);
    const icon = menuItem.find('> .menu-head > .icon');
    const menuBody = menuItem.find('> .menu-body');

    if (icon.hasClass(closeIcon)) {
      icon.removeClass(closeIcon).addClass(openIcon);
      menuBody.removeClass(hide);
    } else {
      icon.removeClass(openIcon).addClass(closeIcon);
      menuBody.addClass(hide);
    }
  });

  // 樹狀選單
  $(document).on('click', '.card-help .help-title i', (event) => {
    const help = $(event.currentTarget.parentNode.parentNode);
    const icon = help.find('.icon');
    const body = help.find('.help-body');

    if (icon.hasClass(closeIcon)) {
      icon.removeClass(closeIcon).addClass(openIcon);
      body.addClass(block);
    } else {
      icon.removeClass(openIcon).addClass(closeIcon);
      body.removeClass(block);
    }
  });

  // 經歷過程時間軸
  $(document).on('click', '.timeline .history-grid i', (event) => {
    const help = $(event.currentTarget.parentNode.parentNode);
    const icon = help.find('.icon');
    const body = help.find('.grid-body');

    if (icon.hasClass(closeIcon)) {
      icon.removeClass(closeIcon).addClass(openIcon);
      body.removeClass(hide);
    } else {
      icon.removeClass(openIcon).addClass(closeIcon);
      body.addClass(hide);
    }
  });

  // 比較議案/條文 的表格分為上下兩塊，在此將上下設為同步滑動
  const compareLawDiffHeaderRow = document.querySelector('.law-compare-wrapper .law-diff-header-row');
  const compareLawDiffRow = document.querySelector('.law-compare-wrapper .law-diff-row:not(.law-diff-header-row)');

  if (compareLawDiffHeaderRow && compareLawDiffRow) {
    compareLawDiffHeaderRow.addEventListener('scroll', (event) => {
      compareLawDiffRow.scrollLeft = event.target.scrollLeft;
    });
    compareLawDiffRow.addEventListener('scroll', (event) => {
      compareLawDiffHeaderRow.scrollLeft = event.target.scrollLeft;
    });
  }

  const smallPageHero = $('.small-page-hero');
  const mainContent = $('.main-content');

  if (smallPageHero.length && mainContent.length) {
    window.addEventListener('scroll', () => {
      const mainContentY = mainContent[0].offsetTop - smallPageHero[0].offsetHeight - 60;

      if (document.documentElement.scrollTop > mainContentY) {
        $('.small-page-hero').addClass('active');
      } else {
        $('.small-page-hero').removeClass('active');
      }
    });
  }

  $('.add-compare-target, .set-compare-target').on('click', () => {
    $('.compare-target-modal').modal('show');
  });
}

document.addEventListener('DOMContentLoaded', init);
