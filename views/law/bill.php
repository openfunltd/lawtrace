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

$version_end_date = $version_selected->日期;
foreach ($versions as $idx => $version) {
    if ($version->版本編號 == $version_selected->版本編號) {
        $version_idx = $idx;
        break;
    }
}
$version_start_date = ($version_idx != count($versions) - 1) ? $versions[$version_idx + 1]->日期 : '1911-01-01';

$bill_GET_params = [
    'output_fields' => [
        '提案編號',
        '議案狀態',
        '提案來源',
        '提案單位/提案委員',
        'url',
        '議案編號',
        '提案日期',
    ],
    'limit' => 500,
];

$bill_GET_array = [];
foreach ($bill_GET_params as $key => $param) {
    if (is_array($param)) {
        foreach ($param as $val) {
            $bill_GET_array[] = "{$key}={$val}";
        }
    } else {
        $bill_GET_array[] = "{$key}={$param}";
    }
}
$bill_GET_str = implode('&', $bill_GET_array);

$res = LYAPI::apiQuery("/law/{$law_id}/bills?{$bill_GET_str}", "查詢關聯的提案 法律編號：{$law_id}");
$bills = $res->bills ?? [];

//filter bills' date within version
$bills = array_filter($bills, function ($bill) use ($version_end_date, $version_start_date) {
    $date = $bill->提案日期;
    return $version_start_date < $date and $date <= $version_end_date;
});
$bill_cnt = count($bills);

//bills order by date DESC
usort($bills, function($b1, $b2) {
    $date_b1 = $b1->提案日期 ?? '';
    $date_b2 = $b2->提案日期 ?? '';
    return $date_b2 <=> $date_b1;
});

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
                  href="/law/bill/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>"
                >
                  <?= $this->escape("{$version->日期} {$version->動作}") ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
      <?php if (empty($bills)) { ?>
        <div class="mt-3 mb-0 fs-4">
          <button type="button" class="btn btn-danger" disabled>無議案資料</button>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="container my-3">
  <div class="btn-group">
    <?php $endpoint = "{$law_id}?version={$version_id_selected}"; ?>
    <a href="/law/show/<?= $this->escape($endpoint) ?>" class="btn btn-primary" aria-current="page">完整條文</a>
    <a href="/law/history/<?= $this->escape($endpoint) ?>" class="btn btn-primary">編修歷程</a>
    <a href="#" class="btn btn-primary active">關聯議案</a>
  </div>
</div>
<?php if (!empty($bills)) { ?>
  <div class="container my-3">
    <p>議案數量：<?= $this->escape($bill_cnt) ?></p>
    <div class="row border px-5 py-0 rounded-2">
      <table class="table table-sm fs-6">
        <thead>
          <tr>
            <th>提案日期</th>
            <th>提案編號</th>
            <th>議案狀態</th>
            <th>提案來源</th>
            <th>提案單位/提案委員</th>
            <th>連結</th>
          </tr>
        <thead>
        <tbody>
          <?php foreach ($bills as $bill) { ?>
            <tr>
              <td><?= $this->escape($bill->提案日期 ?? '') ?></td>
              <td><?= $this->escape($bill->提案編號 ?? '') ?></td>
              <td><?= $this->escape($bill->議案狀態 ?? '') ?></td>
              <td><?= $this->escape($bill->提案來源 ?? '') ?></td>
              <td><?= $this->escape($bill->{'提案單位/提案委員'} ?? '') ?></td>
              <?php
              $bill_id = $bill->議案編號 ?? '';
              $dataly_url = ($bill_id != '') ? "https://dataly.openfun.app/collection/item/bill/{$bill_id}" : '';
              $ppg_url = $bill->url ?? '';
              ?>
              <td>
                <?php if ($ppg_url != '') {?>
                  <a href="<?= $this->escape($ppg_url) ?> " target="_blank">ppg</a>
                <?php } ?>
                <?php if ($dataly_url != '') {?>
                  <a href="<?= $this->escape($dataly_url) ?>" target="_blank">dataly</a>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
<?php } ?>
<?= $this->partial('common/footer') ?>
