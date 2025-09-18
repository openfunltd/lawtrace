<?php
$law_id = $this->law_id;
$sub_law_count = $this->sub_law_count;
$sub_laws = $this->sub_laws;
$this->tab = 'sub_laws';
?>
<?php $law_name = $this->escape($this->law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 子法列表"]) ?>
<div class="main">
  <?= $this->partial('law/law_hero', $this) ?>
  <div class="main-content">
    <section class="search-result">
      <div class="container container-sm">
        <div class="result-info">
          <div>
            總計有 <em><?= $sub_law_count ?> 筆子法</em>，但其中包含已失效子法。失效於否可在全國法規資料庫中確認。
          </div>
        </div>
        <?php foreach ($sub_laws as $sub_law) { ?>
          <div class="search-result-card">
            <div class="law-info">
              <div class="title"><?= $this->escape($sub_law->名稱); ?></div>
              <?php $aliases_merged = $sub_law->aliases_merged; ?>
              <?php if (!empty($aliases_merged)) { ?>
                <div class="alias">其他名稱： <?= $this->escape(implode('、', $aliases_merged)) ?> </div>
              <?php } ?> 
              <a class="btn btn-sm btn-outline-primary mt-1" href="<?= $this->escape($sub_law->law_moj_url)?>" target="_blank">
                查看全國法規資料庫
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
            </div>       
          </div>         
        <?php } ?>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
