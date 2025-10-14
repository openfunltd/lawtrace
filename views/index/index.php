<?php
$this->description = "幫助你了解立法院在審查哪些法案，通過了哪些法案，讓立法過程更透明。";
$news = IndexHelper::getOpenfunLog();

?>
<?= $this->partial('common/header', $this) ?>
<div class="main">
  <section class="page-hero search-form">
    <div class="container container-sm">
      <form action="/search" method="get">
        <div class="brand-name">
          LawTrace
        </div>
        <h2>
          你想暸解什麼法律的沿革或討論現狀？
        </h2>
        <div>
          <input type="search" class="form-control search-input" placeholder="關鍵字" name="q" required>
        </div>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">
            搜尋
          </button>
        </div>
      </form>
    </div>
  </section>

  <div class="main-content">
    <div class="container container">
      <?php if (count($news) > 0) { ?>
        <section class="lawtrace-news">
          <h3>最新消息</h3>
          <div class="news-list">
            <?php foreach ($news as $idx => $news_item) { ?>
              <div class="news-item">
                <div class="date"><?= $this->escape($news_item->date) ?></div>
                <?php if (property_exists($news_item, 'link')) { ?>
                <a href="<?= $this->escape($news_item->link) ?>" target="_blank">
                  <?= $this->escape($news_item->title) ?>
                </a>
                <?php } else { ?>
                <?= $this->escape($news_item->title) ?>
                <?php } ?>
              </div>
              <?php if ($idx == 2) { break; } ?>
            <?php } ?>
          </div>
        </section>
      <?php } ?>
      <section class="law-status-info">
        <h3>
          查看法律條文及修法過程中的提案內容
        </h3>
        <div class="law-status-info-grid">
          <div class="law-status-info-list">
            <div class="list-header">
              <span class="tag tag-green">
                近期三讀通過
              </span>
              <div class="refer">
                動態資訊來源：立法院法律系統
              </div>
            </div>
            <?php foreach ($this->third_read_laws as $law) { ?>
            <a href="/law/compare?source=version:<?= $law['law']->法律編號 ?>:<?= $law['law']->最新版本->日期 ?>" class="law">
            <div class="title">
                <?= $this->escape($law['law']->名稱) ?>
              </div>
              <div class="date">
                  三讀日期：<?= LawVersionHelper::getMinguoDate($law['law']->最新版本->日期) ?>
              </div>
            </a>
            <?php } ?>
          </div>
          <div class="law-status-info-list">
            <div class="list-header">
              <span class="tag tag-turquoise">
                近期出爐審查報告
              </span>
              <div class="refer">
                動態資訊來源：立法院議事暨公報資訊網
              </div>
            </div>
            <?php foreach ($this->exammed_laws as $law) { ?>
            <a href="/law/compare?source=bill:<?= $law->議案編號 ?>" class="law">
              <div class="title">
                  <?= $this->escape($law->{'法律編號:str'}[0]) ?>
              </div>
              <div class="date">
                  審查報告出爐日期：<?= LawVersionHelper::getMinguoDate($law->提案日期) ?>
              </div>
            </a>
            <?php } ?>
          </div>
          <div class="law-status-info-list">
            <div class="list-header">
              <span class="tag tag-brown">
                近期審查會議
              </span>
              <div class="refer">
                動態資訊來源：立法院議事暨公報資訊網
              </div>
            </div>
            <?php foreach ($this->examming_laws as $law) { ?>
            <a href="/law/compare?source=meet:<?= $law['meet']->會議代碼 ?>:<?= $law['law_id'] ?>" class="law">
              <div class="title">
                  <?= $this->escape($law['law_name']) ?>
              </div>
              <div class="committee">
                  審查委員會：<?= $this->escape(implode(',', $law['meet']->{'委員會代號:str'})) ?>
              </div>
              <div class="date">
              開會日期：<?= LawVersionHelper::getMinguoDate($law['meet']->日期[0]) ?>
              </div>
            </a>
            <?php } ?>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
