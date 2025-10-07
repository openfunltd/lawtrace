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
        <p class="mb-1">
          本網站由<a href="https://openfun.tw/" target="_blank">歐噴有限公司</a>開發，<a href="https://www.wfd.org/" target="_blank">西敏寺民主基金會</a>支持，並採用 BSD License (BSD-3-Clause)，歡迎所有人使用與改作。
        </p>
        <ul class="mb-2">
          <li>
          造訪<a href="https://huggingface.co/collections/openfun/tw-legislative-yuan-data-67c7e14902935d02b0b97a3f" target="_blank">資料集</a>與 <a href="https://v2.ly.govapi.tw/" target="_blank">API</a>，瞭解使用技術與取得資料（<span class="dropdown"><span class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">查看本頁使用API</span>
                <ul class="dropdown-menu">
                    <?php foreach (LYAPI::getLogs() as $log) { ?>
                    <li><a class="dropdown-item" href="<?= $this->escape($log[0]) ?>" target="_blank"><?= $this->escape($log[1]) ?></a></li>
                    <?php } ?>
                </ul>
            </span>）
          </li>
          <li>
            加入 <a href="https://g0v-tw.slack.com/archives/CDRE0Q0CE" target="_blank">g0v 國會松頻道</a>交流與協作。若您尚未註冊 g0v slack 帳號，請<a href="https://g0v.hackmd.io/@jothon/joing0vslack#g0v-Slack-%E8%A8%BB%E5%86%8A%E6%AD%A5%E9%A9%9F%E5%9C%96%E8%A7%A3-g0v-Slack-Registration-Tutorial" target="_blank">先到這裡</a>註冊
          </li>
        </ul>
        <p class="my-0">
          如果您在使用本頁面時遇到問題、發現錯誤或有其他建議，歡迎透過<a href="https://forms.gle/LhZUtZbwNpTDyhNC6" target="_blank">這份回報表單</a>告訴我們。
        </p>
      </div>
      <div class="links">
        <a href="https://docs.google.com/document/d/e/2PACX-1vRGme9ddy07-155LOpRkLNlM0b2YU1JPOYPqVW1SXJvMT9x617hkQsJTnmizhjxZN9EW-6UaajqxGTL/pub" target="_blank">權利宣告與網站條款</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vRGme9ddy07-155LOpRkLNlM0b2YU1JPOYPqVW1SXJvMT9x617hkQsJTnmizhjxZN9EW-6UaajqxGTL/pub#h.jicwkxkehpp3" target="_blank">隱私權政策</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vRGme9ddy07-155LOpRkLNlM0b2YU1JPOYPqVW1SXJvMT9x617hkQsJTnmizhjxZN9EW-6UaajqxGTL/pub#h.gay160qbmfy3" target="_blank">資料來源</a>
        <a href="https://docs.google.com/document/d/e/2PACX-1vRGme9ddy07-155LOpRkLNlM0b2YU1JPOYPqVW1SXJvMT9x617hkQsJTnmizhjxZN9EW-6UaajqxGTL/pub#h.e087bb60wai3" target="_blank">取用資料的注意事項</a>
        <?= date('Y') ?> © OpenFun, supported by WFD
      </div>
  </div>
</div>
</div>
</body>
</html>
