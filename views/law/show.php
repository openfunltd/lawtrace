<?php
$law_id = $this->law_id;
$version_id_input = $this->version_id_input;

if (! ctype_digit($law_id)) {
    header('HTTP/1.1 400 Bad Request');
    echo "<h1>400 Bad Request</h1>";
    echo "<p>Invalid law_id</p>";
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
$res = LYAPI::apiQuery("/law/{$law_id}/versions", "查詢 {$law->名稱} 各法律版本");
$versions = $res->lawversions ?? [];
if ($version_id_input != 'latest') {
    $invalid_version = true;
    foreach ($versions as $version) {
        $version_id = $version->版本編號 ?? NULL;
        if ($version_id_input == $version_id) {
            $invalid_version = false;
            $version_id_selected = $version_id;
            $version_selected = $version;
            break;
        }
    }
    if ($invalid_version) {
        header('HTTP/1.1 404 No Found');
        echo "<h1>404 No Found</h1>";
        echo "<p>No version data with version_id {$version_id_input}</p>";
        exit;
    }
}

//versions order by date DESC
usort($versions, function($v1, $v2) {
    $date_v1 = $v1->日期 ?? '';
    $date_v2 = $v2->日期 ?? '';
    return $date_v2 <=> $date_v1;
});
if ($version_id_input == 'latest') {
    foreach ($versions as $version) {
        $version_id = $version->版本編號 ?? NULL;
        if (isset($version_id)) {
            $version_id_selected = $version_id;
            $version_selected = $version;
            break;
        }
    }
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

$aliases = $law->其他名稱 ?? [];
$vernaculars = $law->別名 ?? [];
?>
<?= $this->partial('common/header', ['title' => '法律內容']) ?>
<div class="main">
  <section class="page-hero law-details-info">
    <div class="container">
      <nav class="breadcrumb-wrapper">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="index.html">
              <i class="bi bi-house-door"></i>
            </a>
          </li>
          <li class="breadcrumb-item active">
            法律資訊
          </li>
        </ol>
      </nav>
      <h2 class="light">
        <?= $this->escape($law->名稱 ?? '') ?>
      </h2>
      <div class="info">
        <?php if (!empty($aliases)) { ?>
          <div class="alias">
            別名：<?= $this->escape(implode('、', $aliases)) ?>
          </div>
        <?php } ?>
        <?php if (!empty($vernaculars)) { ?>
          <div class="vernacular">
            俗名：<?= $this->escape(implode('、', $vernaculars)) ?>
          </div>
        <?php } ?>
      </div>
      <div class="btn-group law-pages">
        <a href="#" class="btn btn-outline-primary active">
          瀏覽法律
        </a>
      </div>
    </div>
  </section>

  <div class="main-content">
    <section class="law-details">
      <div class="container">
        <div class="law-version">
          <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
        <div class="law-list-wrapper">
          <?=
          $this->partial('partial/side',[
              'chapters' => $chapters,
              'chapter_units' =>$chapter_units,
          ])
          ?>
          <div class="law-list">
            <?php foreach ($contents as $content) { ?>
              <?php
              $content_order = $content->順序;
              $chapter_name = $content->章名 ?? '';
              $chapter_unit = ($chapter_name != '') ? LawChapterHelper::getChapterUnit($chapter_name) : '';
              $title_level = array_search($chapter_unit, $chapter_units);
              $law_index = $content->條號 ?? '';
              $law_content = $content->內容 ?? '';
              ?>
              <?php if ($title_level !== false) { ?>
                <div
                  id="contentOrder-<?= $this->escape($content_order) ?>"
                  class="title-level-<?= $this->escape($title_level + 1) ?>"
                >
                  <?= $this->escape($chapter_name) ?>
                </div>
              <?php } ?>
              <?php if (!in_array($law_index, ['', '法律名稱'])) { ?>
                <div class="info-card">
                  <div class="card-head">
                    <div class="title">
                      <?= $this->escape($law_index)?>
                    </div>
                  </div>
                  <div class="card-body">
                    <?= nl2br($this->escape($law_content))?>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
