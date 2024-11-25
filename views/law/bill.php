<?php
$law_id = $this->law_id;

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
$bill_cnt = $res->total ?? 0;
$bills = $res->bills ?? [];

//bills order by date DESC
usort($bills, function($b1, $b2) {
    $date_b1 = $b1->提案日期 ?? '';
    $date_b2 = $b2->提案日期 ?? '';
    return $date_b2 <=> $date_b1;
});

?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold display-6"><?= $this->escape($law->名稱 ?? '') ?></h1>
      <?php if (!empty($aliases)) { ?>
        <p class="mt-2 mb-0 fs-5">
          別名：
          <?= $this->escape(implode('、', $aliases)) ?>
        </p>
      <?php } ?>
      <?php if (empty($bills)) { ?>
        <div class="mt-3 mb-0 fs-4">
          <button type="button" class="btn btn-danger" disabled>無議案資料</button>
        </div>
      <?php } ?>
    </div>
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
