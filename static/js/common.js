function init () {
  const closeIcon = 'bi-chevron-down';
  const openIcon = 'bi-chevron-up';
  const hide = 'd-none';
  const block = 'd-block';
  const show = 'show';

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
}

document.addEventListener('DOMContentLoaded', init);
