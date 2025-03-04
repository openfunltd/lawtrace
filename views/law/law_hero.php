<?php
$this->aliases = $this->law->其他名稱 ?? [];
$this->vernaculars = $this->law->別名 ?? [];
$this->aliases = array_merge($this->aliases, $this->vernaculars);
$this->diff_endpoint = "/law/diff/{$this->law_id}";

$postfixes = [];
$postfix = function($type) use (&$postfixes) {
    if ($postfixes[$type] ?? false) {
        return $postfixes[$type];
    }
    return $postfixes['default'] ?? '';
};

if ($this->version_id_input and $this->version_id_input != 'latest') {
    $postfixes['default'] = "?version={$this->version_id_input}";
} else {
    $res = LYAPI::apiQuery("/laws/{$this->law_id}/versions?limit=1&sort=日期>", "查詢法律 {$this->law_id} 最新版本");
    $this->version_id_input = $res->lawversions[0]->版本編號;
}

$tabs = [];
if ('single' != $this->source_type) {
    $tabs[] = ['瀏覽法律', "/law/show/{$this->law_id}" . $postfix('show'), 'show'];
    $tabs[] = ['異動條文', "/law/diff/{$this->law_id}" . $postfix('diff'), 'diff'];
    $tabs[] = ['經歷過程', "/law/history/{$this->law_id}" . $postfix('history'), 'history'];
    if ($this->source ?? false) {
        $tabs[] = ['條文比較工具', "/law/compare/{$this->law_id}" . $postfix('compare'), 'compare'];
    }
}
if ('meet' == $this->source_type) {
    $tabs[] = ['會議原始資料', $this->meet->會議資料[0]->ppg_url ?? '#', 'meet', ['icon' => 'bi bi-box-arrow-up-right']];
} elseif ('bill' == $this->source_type) {
    $tabs[] = ['報告原始資料', $this->bill->url ?? '#', 'bill', ['icon' => 'bi bi-box-arrow-up-right']];
}


if (strpos($this->version_id_input, "{$this->law_id}:") === 0) { // 如果是以 law_id: 開頭的版本，後面應該會是三讀日期動作
    $version_date = substr($this->version_id_input, strlen("{$this->law_id}:"));
}
if ($this->version ?? false) {
    $version_date = sprintf("%s %s",
        LawVersionHelper::getMinguoDate($this->version->日期),
        $this->version->動作
    );
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
            <a href="/law/show/<?= $this->law_id ?>">
              <?= $this->escape($this->law->名稱 ?? '') ?>
            </a>
          </li>
          <?php if ($this->source_type == 'meet') { ?>
            <li class="breadcrumb-item active">
            委員會審查
            </li>
          <?php } elseif ($this->source_type == 'bill') { ?>
            <li class="breadcrumb-item active">
            審查報告
            </li>
          <?php } elseif ($this->source_type == 'version') { ?>
            <li class="breadcrumb-item active">
            三讀版本
            </li>
            <li class="breadcrumb-item active">
            <?= $version_date ?>
            </li>
          <?php } elseif ($this->source_type == 'single') { ?>
            <li class="breadcrumb-item active">
            單一條文
            </li>
            <li class="breadcrumb-item active">
            <?= $this->escape($this->law_content_name) ?>
            </li>
          <?php } elseif ($this->source_type == 'progress') { ?>
            <li class="breadcrumb-item active">
            未議決議案
            </li>
            <li class="breadcrumb-item active">
            <?= sprintf("第 %d 屆", $this->progress_term) ?>
            </li>
          <?php } ?>
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
          <?php foreach ($tabs as $tab) { ?>
          <a href="<?= $tab[1] ?>" class="btn btn-outline-primary <?= $this->if($this->tab == $tab[2], 'active') ?>">
              <?= $this->escape($tab[0]) ?>
              <?php if ($tab[3] ?? false) { ?>
              <i class="<?= $this->escape($tab[3]['icon']) ?>"></i>
              <?php } ?>
          </a>
          <?php } ?>
      </div>
    </div>
  </section>
  <section class="page-hero small-page-hero">
    <div class="container">
        <?= $this->escape($this->law->名稱 ?? '') ?>
    </div>
  </section>
