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
$versions_data = LawVersionHelper::getVersionsData($law_id, $version_id_input);
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

$aliases = $law->其他名稱 ?? [];
$vernaculars = $law->別名 ?? [];
$diff_endpoint = "/law/diff/{$law_id}";
if ($version_id_input != 'latest') {
    $diff_endpoint = $diff_endpoint . "?version={$version_id_input}";
}
?>
<?php $law_name = $this->escape($law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 瀏覽法律"]) ?>
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
        <a href="<?= $this->escape($diff_endpoint) ?>" class="btn btn-outline-primary">
          查看修訂歷程
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
              版本：<?= $this->escape("{$version_selected->民國日期} {$version_selected->動作}") ?>
              <?= ($version_selected->現行版本 == '現行') ? '(現行版本)' : '' ?>
            </button>
            <ul class="dropdown-menu">
              <?php foreach ($versions as $version) { ?>
                <li>
                  <a
                    class="dropdown-item"
                    href="/law/show/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>"
                  >
                    <?= $this->escape("{$version->民國日期} {$version->動作}") ?>
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
              $law_content_id = $content->法條編號 ?? '';
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
                    <div class="actions">
                      <a href="/law/single/<?= $this->escape($law_content_id) ?>">
                        只顯示此法條內容
                        <i class="bi bi-box-arrow-up-right"></i>
                      </a>
                      <div class="dropdown">
                        <span data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots-vertical"></i>
                        </span>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="/law/single/<?= $this->escape($law_content_id) ?>">
                              只顯示此法條內容
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <?php $law_content = mb_ereg_replace('　', '', $law_content); ?>
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
