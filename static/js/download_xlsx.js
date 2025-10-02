$("#download-xlsx").on("click", function() {
  let data = [];
  let titles = ['版本名稱'];
  $(".law-diff-head").each(function(index, element) {
    title_div = $(element).find('.title');
    title_str = title_div.clone().children().remove().end().text().trim();
    titles.push(title_str);
  });
});
