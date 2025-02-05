<?php
$law_content_id = $this->escape($this->law_content_id);
$version_id_input = $this->escape($this->version_id_input);
$id_array = explode(':', $law_content_id);
$law_id = $id_array[0];

$res = LYAPI::apiQuery("/law_content/{$law_content_id}" ,"查詢法律條文：{$law_content_id} ");
$law_content = $res->data ?? new stdClass();
$chapter_name = $law_content->章名 ?? '';
$is_chapter = ($chapter_name != '');
if (empty($law_content) or $is_chapter) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law_content data with law_content_id {$law_content_id}</p>";
    exit;
}

$res = LYAPI::apiQuery("/law/{$law_id}" ,"查詢法律編號：{$law_id} ");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law data with law_id {$law_id}</p>";
    exit;
}
$law = $res->data;

$law_content_name = $law_content->條號 ?? '';
$versions_data = LawVersionHelper::getVersionsForSingle($law_id, $version_id_input, $law_content_name);
if (is_null($versions_data)) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No versions data with law_id {$law_id}</p>";
    exit;
}
$versions = $versions_data->versions;
$version_selected = $versions_data->version_selected;
$version_id_selected = $versions_data->version_id_selected;
if (is_null($version_selected)) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No version data with version_id {$version_id_input}</p>";
    exit;
}

$res = LYAPI::apiQuery(
    "/law_contents?版本編號={$version_id_selected}&limit=1000",
    "{查詢法律版本為 {$version_id_selected} 的法律條文 }"
);
$contents = $res->lawcontents ?? [];
//TODO 當 API 回傳空的 lawcontents 時要在頁面上呈現/說明

$chapters = array_filter($contents, function($content) {
    $chapter_name = $content->章名 ?? '';
    $chapter_unit = ($chapter_name != '') ? LawChapterHelper::getChapterUnit($chapter_name) : '';

    //要剔除把法律名稱又放進去章名的狀況 example: 民法第二編 債 law_id:04509
    return !in_array($chapter_unit, ['','法']);
});
$chapter_units = LawChapterHelper::getChapterUnits($chapters);
$law_content_order = $law_content->順序;
$target_unit = '';
$chapter_breadcrumbs = [];
while(!empty($chapters)) {
    $chapters_above = array_filter($chapters, function ($chapter) use ($law_content_order, $target_unit){
        $is_target_unit = true;
        if ($target_unit != '') {
            $chapter_name = $chapter->章名;
            $chapter_unit = LawChapterHelper::getChapterUnit($chapter_name);
            $is_target_unit = ($chapter_unit == $target_unit);
        }
        $distance = $law_content_order - ($chapter->順序);
        return $distance > 0 and $is_target_unit;
    });
    if (empty($chapters_above)) {
        break;
    }
    $target_chapter = end($chapters_above);
    $target_chapter_name = $target_chapter->章名;
    $chapter_breadcrumbs[] = $target_chapter_name;

    $target_unit = LawChapterHelper::getChapterUnit($target_chapter_name);
    $target_unit_idx = array_search($target_unit, $chapter_units);
    if ($target_unit_idx === 0) {
        break;
    }
    $target_unit = $chapter_units[$target_unit_idx - 1];
}
$chapter_breadcrumbs = array_reverse($chapter_breadcrumbs);

$law_name = $law->名稱 ?? '';
$law_content_text = $law_content->內容 ?? '';
?>
<?= $this->partial('common/header', ['title' => '法律內容']) ?>
<div class="main">
  <section class="page-hero law-details-info">
    <div class="container">
      <nav class="breadcrumb-wrapper">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="/">
              <i class="bi bi-house-door"></i>
            </a>
          </li>
          <li class="breadcrumb-item">
            <a href="/law/show/<?= $this->escape($law_id) ?>">
              法律資訊
            </a>
          </li>
          <li class="breadcrumb-item active">
            單一條文
          </li>
        </ol>
      </nav>
      <h2 class="light">
        <?= $this->escape($law_name) . ' | ' . $this->escape($law_content_name)?>
      </h2>
    </div>
  </section>

  <div class="main-content">
    <section class="law-details">
      <div class="container">
        <div class="law-version">
          <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              版本：<?= $this->escape("{$version_selected->民國日期} {$version_selected->動作}") ?>
              <?= ($version_selected->現行版本 == '現行') ? '(現行版本)' : '' ?>
            </button>
            <ul class="dropdown-menu">
              <?php foreach ($versions as $version) { ?>
                <?php $law_content_id = $version->law_content_id; ?>
                <li>
                  <a
                    class="dropdown-item"
                    href="/law/single/<?= $this->escape($law_content_id) ?>"
                  >
                    <?= $this->escape("{$version->民國日期} {$version->動作}") ?>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </div>
        </div>
        <div class="single-law">
          <nav class="breadcrumb-wrapper">
            <ol class="breadcrumb">
              <li class="breadcrumb-item">
                <a href="/law/show/<?= $this->escape($law_id) ?>">
                  <?= $this->escape($law_name) ?>
                </a>
              </li>
              <?php foreach ($chapter_breadcrumbs as $breadcrumb) { ?>
                <li class="breadcrumb-item">
                  <?= $this->escape($breadcrumb) ?>
                </li>
              <?php } ?>
            </ol>
          </nav>
          <div class="info-card">
            <div class="card-head">
              <div class="title">
                <?= $this->escape($law_content_name) ?>
              </div>
            </div>
            <div class="card-body">
              <?php $law_content_text = mb_ereg_replace('　', '', $law_content_text); ?>
              <?= nl2br($this->escape($law_content_text)) ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
