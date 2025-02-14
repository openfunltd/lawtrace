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
    <div class="container container-sm about-law-trace">
      <section class="refer">
        <h3 class="title">
          LawTrace 的資料來源
        </h3>
        <ul>
          <li><a href="https://lis.ly.gov.tw/lglawc/lglawkm" target="_blank">立法院法律系統</a></li>
          <li><a href="https://ppg.ly.gov.tw/ppg/" target="_blank">立法院議事暨公報資訊網</a></li>
        </ul>
      </section>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
