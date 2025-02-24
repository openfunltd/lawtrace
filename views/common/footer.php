<div class="lt-footer">
  <div class="container">
      <?php if ($_GET['debug'] ?? false) { ?>
      <ul>
          <?php foreach (LYAPI::getLogs() as $log) { ?>
          <?php list($url, $text) = $log ?>
          <?php $query_string = parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY); ?>
          <?php $query_string = rtrim($query_string, '?'); ?>
          <li><a href="<?= $this->escape($url) ?>" target="_blank"><?= $this->escape($query_string) ?>: <?= $this->escape($text) ?></a></li>
          <?php } ?>
      </ul>
      <?php } ?>
  </div>
  <div class="container">
    <div class="logo">
      <img src="/static/images/logo_w.svg" alt="LawTrace">
    </div>
    <div class="footer-grid">
      <div class="info">
        本網站由<a href="https://openfun.tw/" target="_blank">歐噴有限公司</a>開發，<a href="https://www.wfd.org/" target="_blank">西敏寺民主基金會</a>支持，並採用 BSD License (BSD-3-Clause)，歡迎所有人使用與改作。
        <ul>
          <li>
             造訪<a href="https://huggingface.co/openfun" target="_blank">資料集</a>與 <a href="https://v2.ly.govapi.tw/" target="_blank">API</a>，瞭解使用技術與取得資料
          </li>
          <li>
            加入 <a href="https://g0v-tw.slack.com/archives/CDRE0Q0CE" target="_blank">g0v 國會松頻道</a>交流與協作。若您尚未註冊 g0v slack 帳號，請<a href="https://g0v.hackmd.io/@jothon/joing0vslack#g0v-Slack-%E8%A8%BB%E5%86%8A%E6%AD%A5%E9%A9%9F%E5%9C%96%E8%A7%A3-g0v-Slack-Registration-Tutorial" target="_blank">先到這裡</a>註冊
          </li>
        </ul>
      </div>
      <div class="links">
        <a href="https://docs.google.com/document/d/e/2PACX-1vS_q7Zh0rOBQlNfIJrkS3BaIu_MqNubVVBrBstV7OCWpGOc9C4smmwy-7VvXLAgSc7mW6bMWLOc2ONX/pub#h.tf3fy8d94hlq" target="_blank">著作權宣告</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vS_q7Zh0rOBQlNfIJrkS3BaIu_MqNubVVBrBstV7OCWpGOc9C4smmwy-7VvXLAgSc7mW6bMWLOc2ONX/pub#h.pmsr6517h229" target="_blank">網站使用條款</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vS_q7Zh0rOBQlNfIJrkS3BaIu_MqNubVVBrBstV7OCWpGOc9C4smmwy-7VvXLAgSc7mW6bMWLOc2ONX/pub#h.irfg4588x2dn" target="_blank">隱私權政策</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vS_q7Zh0rOBQlNfIJrkS3BaIu_MqNubVVBrBstV7OCWpGOc9C4smmwy-7VvXLAgSc7mW6bMWLOc2ONX/pub#h.uy6xwrukf5x1" target="_blank">資料來源</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vS_q7Zh0rOBQlNfIJrkS3BaIu_MqNubVVBrBstV7OCWpGOc9C4smmwy-7VvXLAgSc7mW6bMWLOc2ONX/pub#h.fld3c94c20hd" target="_blank">取用資料的注意事項</a>
      </div>
  </div>
</div>
</div>
</body>
</html>
