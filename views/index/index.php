<?= $this->partial('common/header', ['title' => 'LawTrace']) ?>
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
      <section class="law-status-info">
        <h3>
          查看法律條文及修法過程中的提案內容
        </h3>
        <div class="law-status-info-grid">
          <div class="law-status-info-list">
            <span class="tag tag-green">
              近期三讀通過
            </span>
            <div class="refer">
              動態資訊來源：立法院法律系統
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
            <span class="tag tag-turquoise">
              近期出爐審查報告
            </span>
            <div class="refer">
              動態資訊來源：立法院議事暨公報資訊網
            </div>
            <a href="#" class="law">
              <div class="title">
                野生動物保育法
              </div>
              <div class="date">
                審查報告出爐日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                國家運動產業發展中心設置條例
              </div>
              <div class="date">
                審查報告出爐日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                運動部全民運動署組織法
              </div>
              <div class="date">
                審查報告出爐日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                壯世代政策與產業發展促進法
              </div>
              <div class="date">
                審查報告出爐日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                野生動物保育法
              </div>
              <div class="date">
                審查報告出爐日期：113/08/07
              </div>
            </a>
          </div>
          <div class="law-status-info-list">
            <span class="tag tag-brown">
              七天內將審查
            </span>
            <div class="refer">
              動態資訊來源：立法院議事暨公報資訊網
            </div>
            <a href="#" class="law">
              <div class="title">
                野生動物保育法
              </div>
              <div class="committee">
                審查委員會：ＯＯＯＯ委員會
              </div>
              <div class="date">
                開會日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                國家運動產業發展中心設置條例
              </div>
              <div class="committee">
                審查委員會：ＯＯＯＯ委員會
              </div>
              <div class="date">
                開會日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                運動部全民運動署組織法
              </div>
              <div class="committee">
                審查委員會：ＯＯＯＯ委員會
              </div>
              <div class="date">
                開會日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                壯世代政策與產業發展促進法
              </div>
              <div class="committee">
                審查委員會：ＯＯＯＯ委員會
              </div>
              <div class="date">
                開會日期：113/08/07
              </div>
            </a>
            <a href="#" class="law">
              <div class="title">
                野生動物保育法
              </div>
              <div class="committee">
                審查委員會：ＯＯＯＯ委員會
              </div>
              <div class="date">
                開會日期：113/08/07
              </div>
            </a>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
