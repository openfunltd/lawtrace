<?= $this->partial('common/header', ['title' => 'Lawtrace']) ?>
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
      <section class="concept">
        <h2 class="title">
          一站式地查詢法律相關資料
        </h2>
        <div class="desc">
          法律相關的資料時常散落在各處，找起來挺不簡單的，所以 LawTrace 將資訊彙整在一處，讓找尋資料時，能少開幾個分頁、更有效率。祝查找愉快！
        </div>
      </section>

      <section class="features">
        <div class="feature-item">
          <div class="icon">
            <img src="static/images/feature1.svg" alt="瀏覽各版本的法律內容">
          </div>
          <div class="title">
            瀏覽各版本的法律內容
          </div>
          <div class="desc">
            從新增條文到後續所有修正版本，均可一覽無遺，快速掌握條文的演變過程。
          </div>
        </div>

        <div class="feature-item">
          <div class="icon">
            <img src="static/images/feature2.svg" alt="查看修訂歷程">
          </div>
          <div class="title">
            查看修訂歷程
          </div>
          <div class="desc">
            一覽法律/法條修訂過程中變更的細節，以及經歷的流程，如：一二三讀、審查會議、公聽會等等。
          </div>
        </div>

        <div class="feature-item">
          <div class="icon">
            <img src="static/images/feature3.svg" alt="比較相關議案/條文">
          </div>
          <div class="title">
            比較相關議案/條文
          </div>
          <div class="desc">
            將各議案中的條文草案或不同版本的條文並排呈現，比較差異。
          </div>
        </div>
      </section>

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
