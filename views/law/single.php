<?php
$law_content_id = $this->escape($this->law_content_id);
$version_id_input = $this->escape($this->version_id_input);
$id_array = explode(':', $law_content_id);
$law_id = $id_array[0];

$res = LYAPI::apiQuery("/law_content/{$law_content_id}" ,"查詢法律條文：{$law_content_id} ");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law_content data with law_content_id {$law_content_id}</p>";
    exit;
}
$law_content = $res->data;

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

$law_name = $law->名稱 ?? '';
$law_content_name = $law_content->條號 ?? '';
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
          <li class="breadcrumb-item">
            <a href="/law/show/<?= $law_id ?>">
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
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
