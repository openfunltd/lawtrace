<?php
$this->title = '關於我們';
$this->description = '我們透過研究發現，即使是倡議團體、媒體記者、立委助理、事實查核團體等經常查詢立法院資料的專業工作者，在檢索法案資料時，依舊需要耗費大量時間，跨越多個網站與開啟一長串的分頁，或是埋首在數百頁的文件中才能找到所需資訊。

對於不常使用立法院網站的一般民眾來說，想查法案資料更是困難重重。資料難以取得，背離立法院公開資料的初衷，公民因此難以關注修法進程，民主精神難以落實。';
?>
<?= $this->partial('common/header', $this) ?>
<div class="main">
  <section class="page-hero">
    <div class="container container-sm">
      <div class="brand-name">
        LawTrace
      </div>
      <h2>
        關於我們
      </h2>
    </div>
  </section>
  <div class="main-content">
    <div class="container container-sm about-law-trace">
      <section class="concept">
        <h2 class="title">
          專案緣起
        </h2>
        <div class="desc">
          <p>我們<a href="https://openfun.tw/ly-user-study/" target="_blank">透過研究</a>發現，即使是倡議團體、媒體記者、立委助理、事實查核團體等經常查詢立法院資料的專業工作者，在檢索法案資料時，依舊需要耗費大量時間，跨越多個網站與開啟一長串的分頁，或是埋首在數百頁的文件中才能找到所需資訊。</p>
          <p>對於不常使用立法院網站的一般民眾來說，想查法案資料更是困難重重。資料難以取得，背離立法院公開資料的初衷，公民因此難以關注修法進程，民主精神難以落實。</p>
        </div>
      </section>
      <section class="concept">
        <h2 class="title">
          降低倡議團體、媒體、立法團隊取得資料的門檻
        </h2>
        <div class="desc">
          LawTrace 將資訊彙整在一處。找尋資料時，我們可以少開幾個分頁、更有效率，查找的更愉快！
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
      <section class="concept">
        <h2 class="title">
          開發團隊
        </h2>
        <div class="desc">
          <h3><a href="https://openfun.tw/" target="_blank">歐噴有限公司</a></h3>
          <p>歐噴擁有專業的資料爬蟲技術、文字摘要能力，以及處理大型數據、文字、影音資料的豐富經驗。此外，我們擁有國內最頂尖的立法院國會資料處理技術，瞭解利害關係人對立法資訊的需求與痛點。</p>
          <p>歐噴開發 LawTrace ，期望透過提升資料易用性，讓立法與修法的過程能被更多人看見、促進基於真實資料的公共議題討論。</p>
          <p>我們希望以技術為橋樑，透過開放資料，打造更加開放與包容的民主社會。</p>
          <p>本網站由<a href="https://www.wfd.org/" target="_blank">西敏寺民主基金會 (Westminster Foundation for Demoracy)</a>支持開發。</p>
        </div>
      </section>
      <section class="concept">
        <h2 class="title">
          合作夥伴
        </h2>
        <div class="desc">
          <p>歐噴與幾位可靠的夥伴合作，一起開發 LawTrace</p>
          <ul>
            <li><a href="https://www.linkedin.com/in/claire-cheng-32b991123/" target="_blank">Claire Cheng</a> / 專案經理</li>
            <li>Sandra Lin / 使用者研究與設計師</li>
            <li><a href="https://boggy.tw/" target="_blank">Boggy Jan</a> / 前端設計師</li>
          <ul>
        </div>
      </section>
      <section class="concept">
        <h2 class="title">
          加入開放國會的行列
        </h2>
        <div class="desc">
          <h3>零時政府（g0v） 國會松</h3>
          <p>這是由一群關心國會透明與開放的公民們自行發起的專案。我們每月舉辦一次活動，聚焦國會監督、資料治理、介面設計與測試等議題。透過跨界合作，拉近民間與政局距離，深化民主影響力。</p>
          <p>歡迎<a href="https://g0v-tw.slack.com/archives/CDRE0Q0CE" target="_blank">加入 g0v slack 與我們一起交流</a>（若你尚未註冊 g0v slack 帳號，請先<a href="https://join.g0v.tw/" target="_blank">由此註冊</a>），一起推動改變！</p>
        </div>
        <div class="desc">
          <h3>清理過的立法院開放資料在這！歡迎協作與應用</h3>
          <p>歐噴有限公司爬梳來自不同立法院網站的資料，逐一清理成符合開放資料定義的資料。歡迎探索<a href="https://huggingface.co/collections/openfun/tw-legislative-yuan-data-67c7e14902935d02b0b97a3f" target="_blank">資料集</a>與 <a href="https://v2.ly.govapi.tw/" target="_blank">API</a>，發揮創意打造各種創新應用。</p>
          <p>對立法院資料有各種好點子嗎？無論是倡議、研究或社會觀察，歡迎<a href="https://g0v-tw.slack.com/archives/CDRE0Q0CE" target="_blank">加入 g0v slack</a>，這裡有開發者、倡議者、記者、熱血公民，一起分享交流國會資料的各種運用方式吧。</p>
        </div>
      </section>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
