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
  <section class="page-hero small-page-hero">
    <div class="container">
        <?= $this->escape($this->law->名稱 ?? '') ?>
    </div>
  </section>
