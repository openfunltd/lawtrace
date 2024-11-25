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
    "/law_contents?版本編號={$version_id_selected}&limit=200",
    "{查詢法律版本為 {$version_id_selected} 的法律條文 }"
);
$contents = $res->lawcontents ?? [];
//TODO 當 API 回傳空的 lawcontents 時要在頁面上呈現/說明


$aliases = $law->其他名稱 ?? [];
?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<div class="container bg-light bg-gradient mt-5 mb-3 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold display-6"><?= $this->escape($law->名稱 ?? '') ?></h1>
      <?php if (!empty($aliases)) { ?>
        <p class="mt-2 mb-0 fs-5">
          別名：
          <?= $this->escape(implode('、', $aliases)) ?>
        </p>
      <?php } ?>
      <?php if (!empty($versions)) { ?>
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
      <?php } ?>
      <?php if (empty($versions) or empty($contents)) { ?>
        <div class="mt-3 mb-0 fs-4">
          <?php if (empty($versions)) { ?>
            <button type="button" class="btn btn-danger" disabled>無版本資料</button>
          <?php } ?>
          <?php if (empty($contents)) { ?>
            <button type="button" class="btn btn-danger" disabled>無條文資料</button>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="container my-3">
  <div class="btn-group">
    <?php $endpoint = "{$law_id}?version={$version_id_selected}"; ?>
    <a href="#" class="btn btn-primary active" aria-current="page">完整條文</a>
    <a href="/law/history/<?= $this->escape($endpoint) ?>" class="btn btn-primary">編修歷程</a>
    <a href="/law/bill/<?= $this->escape($endpoint) ?>" class="btn btn-primary">關聯議案</a>
  </div>
</div>
<?php if (!empty($contents)) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-2">
      <table class="table fs-6">
        <tbody>
          <?php foreach ($contents as $content) { ?>
            <tr>
              <td class="fw-bold" style="width: 10%;"><?= $this->escape($content->章名 ?? '') ?></td>
              <td style="width: 10%;"><?= $this->escape($content->條號 ?? '') ?></td>
              <td><?= nl2br($this->escape($content->內容 ?? '')) ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
<?php } ?>
<?= $this->partial('common/footer') ?>
