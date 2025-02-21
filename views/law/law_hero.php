<?php
$this->aliases = $this->law->其他名稱 ?? [];
$this->vernaculars = $this->law->別名 ?? [];
$this->diff_endpoint = "/law/diff/{$law_id}";
if ($version_id_input != 'latest') {
    $this->diff_endpoint = $this->diff_endpoint . "?version={$version_id_input}";
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
          <?= $this->escape($this->law->名稱 ?? '') ?>
      </h2>
      <div class="info">
      <?php if (!empty($this->aliases)) { ?>
          <div class="alias">
              別名：<?= $this->escape(implode('、', $this->aliases)) ?>
          </div>
        <?php } ?>
        <?php if (!empty($this->vernaculars)) { ?>
          <div class="vernacular">
              俗名：<?= $this->escape(implode('、', $this->vernaculars)) ?>
          </div>
        <?php } ?>
      </div>
      <div class="btn-group law-pages">
        <a href="#" class="btn btn-outline-primary active">
          瀏覽法律
        </a>
        <a href="<?= $this->escape($this->diff_endpoint) ?>" class="btn btn-outline-primary">
          查看修訂歷程
        </a>
      </div>
    </div>
  </section>

