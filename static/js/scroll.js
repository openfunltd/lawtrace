function scroll_compare_horizontal(direction) {
  cellWidth = $('.law-diff-head.original').first().outerWidth(true);;
  isChecked = $('#splitContent').prop('checked');
  if (isChecked) {
    cellWidth += 1;
  } else {
    cellWidth += 2;
  }

  divGrid = $('.law-diff-row.law-diff-header-row');
  if (direction === 'right') {
    divGrid.animate({scrollLeft: '+=' + cellWidth}, 'smooth');
  } else if (direction === 'left') {
    divGrid.animate({scrollLeft: '-=' + cellWidth}, 'smooth');
  }
}
