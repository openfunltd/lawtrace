function init () {
  // 顯示範圍區塊下拉選單
  $(document).on('click', '.dropdown-select .selected-item', (event) => {
    const select = $(event.currentTarget.parentNode);
    const icon = select.find('.icon');
    const menu = select.find('.select-list');

    if (icon.hasClass('bi-chevron-up')) {
      icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
      menu.addClass('show')
    } else {
      icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
      menu.removeClass('show')
    }
  });

  // 樹狀選單
  $(document).on('click', '.side-menu .menu-head i', (event) => {
    const menuItem = $(event.currentTarget.parentNode.parentNode);
    const icon = menuItem.find('> .menu-head > .icon');
    const menuBody = menuItem.find('> .menu-body');

    if (icon.hasClass('bi-chevron-up')) {
      icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
      menuBody.removeClass('d-none')
    } else {
      icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
      menuBody.addClass('d-none')
    }
  });

  // 樹狀選單
  $(document).on('click', '.card-help .help-title i', (event) => {
    const help = $(event.currentTarget.parentNode.parentNode);
    const icon = help.find('.icon');
    const body = help.find('.help-body');

    if (icon.hasClass('bi-chevron-up')) {
      icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
      body.addClass('d-block')
    } else {
      icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
      body.removeClass('d-block')
    }
  });

  // 經歷過程時間軸
  $(document).on('click', '.timeline .history-grid i', (event) => {
    const help = $(event.currentTarget.parentNode.parentNode);
    const icon = help.find('.icon');
    const body = help.find('.grid-body');

    if (icon.hasClass('bi-chevron-up')) {
      icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
      body.removeClass('d-none')
    } else {
      icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
      body.addClass('d-none')
    }
  });
}

document.addEventListener('DOMContentLoaded', init);