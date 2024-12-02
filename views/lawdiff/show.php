<?php
$bill_no = $this->bill_no;
if (! ctype_digit($bill_no)) {
    header('HTTP/1.1 400 Bad Request');
    echo "<h1>400 Bad Request</h1>";
    echo "<p>Invalid bill_no</p>";
    exit;
}

$res = LYAPI::apiQuery("/bill/{$bill_no}", "查詢議案資料 編號: {$bill_no}");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No bill data with bill_no {$bill_no}</p>";
    exit;
}

$bill = $res->data;
$comparations = $bill->對照表 ?? [];
?>
<?= $this->partial('common/header', ['title' => 'Law Diff']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold fs-2"><?= $this->escape($bill->議案名稱 ?? '') ?></h1>
      <p class="mt-2 mb-0 fs-5"><?= $this->escape($bill->{'提案單位/提案委員'} ?? '') ?></p>
    </div>
  </div>
</div>
<?php if (empty($comparations)) { ?>
  <div class="container my-3 p-3 bg-danger rounded-3">
    <span class="fs-5">無法律對照表</span>
  </div>
<?php } else { ?>
  <div class="container my-3">
    <p class="mt-2 mb-0 fs-5 fw-bold">法律對照表</p>
  </div>
<?php } ?>
<?= $this->partial('common/footer') ?>
