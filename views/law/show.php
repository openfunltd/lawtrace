<?php
$law_id = $this->law_id;
$version_id_selected = $this->version_id_selected;
$law = $this->law;
$versions = $this->versions;
$version_selected = $this->version_selected;

$aliases = $law->其他名稱 ?? [];
?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold display-6"><?= $this->escape($law->名稱 ?? '') ?></h1>
      <?php if (!empty($aliases)) { ?>
        <p class="mt-2 mb-0 fs-5">
          別名：
          <?= $this->escape(implode('、', $aliases)) ?>
        </p>
      <?php } ?>
      <div class="mt-3 mb-0 fs-5 btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
          版本：<?= $this->escape("{$version_selected->日期} {$version_selected->動作}") ?>
        </button>
        <ul class="dropdown-menu">
          <?php foreach ($versions as $version) { ?>
            <li>
              <a
                class="dropdown-item"
                href="/law/show/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>"
              >
                <?= $this->escape("{$version->日期} {$version->動作}") ?>
              </a>
            </li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
