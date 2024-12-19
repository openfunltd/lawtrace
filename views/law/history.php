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
$aliases = $law->其他名稱 ?? [];

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
$histories = $version_selected->歷程 ?? [];
$histories = array_reverse($histories);
//histories order by date DESC
usort($histories, function($h1, $h2) {
    $date_h1 = $h1->會議日期 ?? '';
    $date_h2 = $h2->會議日期 ?? '';
    return $date_h2 <=> $date_h1;
});

?>
<?= $this->partial('common/header-old', ['title' => 'Lawtrace 搜尋']) ?>
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
                  href="/law/history/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>"
                >
                  <?= $this->escape("{$version->日期} {$version->動作}") ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
      <?php if (empty($versions)) { ?>
        <div class="mt-3 mb-0 fs-4">
          <button type="button" class="btn btn-danger" disabled>無版本資料</button>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="container my-3">
  <div class="btn-group">
    <?php $endpoint = "{$law_id}?version={$version_id_selected}"; ?>
    <a href="/law/show/<?= $this->escape($endpoint) ?>" class="btn btn-primary" aria-current="page">完整條文</a>
    <a href="#" class="btn btn-primary active">編修歷程</a>
  </div>
</div>
<?php if (!empty($histories)) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-2">
      <table class="table table-sm fs-6">
        <thead>
          <tr>
            <th>進度</th>
            <th>會議日期</th>
            <th>公報編號</th>
            <th>立法紀錄</th>
            <th>主提案</th>
            <th>連結</th>
          </tr>
        <thead>
        <tbody>
          <?php foreach ($histories as $history) { ?>
            <tr>
              <td style="width: 10%;"><?= $this->escape($history->進度 ?? '') ?></td>
              <td style="width: 10%;"><?= $this->escape($history->會議日期 ?? '') ?></td>
              <td style="width: 10%;"><?= $this->escape($history->公報編號 ?? '') ?></td>
              <td><?= $this->escape($history->立法紀錄 ?? '') ?></td>
              <td><?= $this->escape($history->主提案 ?? '-') ?></td>
              <td>
                <?php $agenda_data = gazetteHelper::getAgendaData($history->立法紀錄); ?>
                <?php if (isset($agenda_data[0])) { ?>
                  <a href="<?= $agenda_data[0]  ?>" title="公報" target="_blank">
                    <span class="material-symbols-outlined">full_coverage</span><!--
                  --></a>
                <?php } ?>
                <?php if (isset($agenda_data[1])) { ?>
                  <a href="<?= $agenda_data[1] ?>" title="公報章節" target="_blank">
                    <span class="material-symbols-outlined">newspaper</span><!--
                  --></a>
                <?php } ?>
                <?php
                $related_docs = $history->關係文書 ?? [];
                foreach ($related_docs as $related_doc) {
                ?>
                  <?php
                  $url = $related_doc->連結 ?? '';
                  $url_type = $related_doc->類型 ?? '';
                  $bill_no = $related_doc->billNo ?? '';
                  ?>
                  <?php if ($bill_no != '') { ?>
                    <a
                      href="/lawdiff/show/<?= $this->escape($bill_no) ?>"
                      title="lawdiff:議案:<?= $this->escape($bill_no) ?>"
                    >
                      <span class="material-symbols-outlined">text_compare</span><!--
                    --></a>
                  <?php } ?>
                  <?php if ($url != '') { ?>
                    <a href="<?= $this->escape($url) ?>" target="_blank" title="立法院法律系統:關係文書:<?= $this->escape($url_type) ?>">
                      <span class="material-symbols-outlined">file_open</span><!--
                    --></a>
                  <?php } ?>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
<?php } ?>
<?= $this->partial('common/footer-old') ?>
