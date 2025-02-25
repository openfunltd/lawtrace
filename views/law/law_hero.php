<?php
$this->aliases = $this->law->其他名稱 ?? [];
$this->vernaculars = $this->law->別名 ?? [];
$this->aliases = array_merge($this->aliases, $this->vernaculars);
$this->diff_endpoint = "/law/diff/{$this->law_id}";
if ($this->version_id_input != 'latest') {
    $this->diff_endpoint = $this->diff_endpoint . "?version={$this->version_id_input}";
}
?>
    <section class="page-hero law-details-info">
    <div class="container">
      <nav class="breadcrumb-wrapper">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="/">
              <i class="bi bi-house-door"></i>
            </a>
          </li>
          <li class="breadcrumb-item active">
            法律資訊
          </li>
        </ol>
      </nav>
      <h2 class="light">
          <?php if ($this->source_type == 'meet') { ?>
          審查會議相關條文比較｜
          <?php } elseif ($this->source_type == 'bill') { ?>
          審查報告相關條文比較｜
          <?php } elseif ($this->source_type == 'version') { ?>
          三讀相關條文比較｜
          <?php } ?>
          <?= $this->escape($this->law->名稱 ?? '') ?>
      </h2>
      <div class="info">
      <?php if (!empty($this->aliases)) { ?>
          <div class="alias">
              其他名稱
              <i class="bi bi-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="過往曾在三讀通過的版本中使用的名稱，或是在提案、公文中曾出現的其他稱呼。"></i>
              ：<?= $this->escape(implode('、', $this->aliases)) ?>
          </div>
      <?php } ?>
      <?php if ($this->source_type == 'meet') { ?>
      <div class="review-committee">
          審查委員會：<?= $this->escape(implode('、', $this->meet->{'委員會代號:str'})) ?>
      </div>
      <div class="review-date">
          審查會議日期：<?= $this->escape(LawVersionHelper::getMinguoDate($this->meet->日期[0])) ?>
      </div>
      <div class="convener">
        召委：
        <!--<img src="images/party/tpp.svg">-->
        <?= $this->escape($this->meet->會議資料[0]->委員會召集委員 ?? '') ?>
      </div>
      <?php } elseif ($this->source_type == 'bill') { ?>
      <div class="review-committee">
          審查委員會：<?= $this->escape(str_replace('本院', '', $this->bill->{'提案單位/提案委員'})) ?>
      </div>
      <div class="review-date">
          審查會發文日期：<?= $this->escape(LawVersionHelper::getMinguoDate($this->bill->議案流程[0]->日期[0] ?? '')) ?>
      </div>
      <?php } ?>
      </div>
      <div class="btn-group law-pages">
          <?php if ('meet' == $this->source_type) { ?>
          <a href="<?= $this->escape($this->meet->會議資料[0]->ppg_url ?? '#') ?>" class="btn btn-outline-primary" target="_blank">
            會議原始資料
            <i class="bi bi-box-arrow-up-right"></i>
          </a>
          <?php } elseif ('bill' == $this->source_type) { ?>
          <a href="<?= $this->escape($this->bill->url ?? '#') ?>" class="btn btn-outline-primary" target="_blank">
            報告原始資料
            <i class="bi bi-box-arrow-up-right"></i>
          </a>
          <?php } ?>
          <a href="/law/show/<?= $this->law_id ?>" class="btn btn-outline-primary <?= $this->if($this->tab == 'show','active') ?>">
          瀏覽法律
        </a>
        <a href="<?= $this->escape($this->diff_endpoint) ?>" class="btn btn-outline-primary <?= $this->if($this->tab == 'log', 'active') ?>">
          查看修訂歷程
        </a>
      </div>
    </div>
  </section>
  <section class="page-hero small-page-hero">
    <div class="container">
        <?= $this->escape($this->law->名稱 ?? '') ?>
    </div>
  </section>
