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

$versions_data = LawVersionHelper::getVersions($law_id, $version_id_input);
$versions = $versions_data->versions;
$version_selected = $versions_data->version_selected;
$version_id_selected = $versions_data->version_id_selected;
if (is_null($version_selected)) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No version data with version_id {$version_id_input}</p>";
    exit;
}

$aliases = $law->其他名稱 ?? [];
$vernaculars = $law->別名 ?? [];
$show_endpoint = "/law/show/{$law_id}";
$history_endpoint = "/law/history/{$law_id}";
if ($version_id_input != 'latest') {
    $show_endpoint = $show_endpoint . "?version={$version_id_input}";
    $history_endpoint = $history_endpoint . "?version={$version_id_input}";
}
?>
<?= $this->partial('common/header', ['title' => '異動條文']) ?>
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
        <a href="<?= $this->escape($show_endpoint) ?>" class="btn btn-outline-primary">
          瀏覽法律
        </a>
        <a href="#" class="btn btn-outline-primary active">
          查看修訂歷程
        </a>
      </div>
    </div>
  </section>
  <div class="main-content">
    <section class="law-details">
      <div class="container">
        <div class="law-list-wrapper">
          <div class="side">
            <div class="law-sections">
              <div class="title">
                選擇版本
              </div>
              <div class="side-menu version-menu">
                <?php foreach ($versions as $version) { ?>
                  <div class="menu-item level-1">
                    <?php if ($version->版本編號 == $version_id_input) {?>
                      <div class="menu-head active">
                    <?php } else {?>
                      <div class="menu-head">
                    <?php }?>
                      <a href="/law/history/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>">
                        <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                      </a>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
          <div>
            <ul class="nav nav-tabs">
              <li class="nav-item">
                <a class="nav-link active" href="#">異動條文</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?= $this->escape($history_endpoint) ?>">經歷過程</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
