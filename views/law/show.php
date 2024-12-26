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
</div>
<?= $this->partial('common/footer') ?>
