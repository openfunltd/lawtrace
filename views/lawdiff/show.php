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
$compare = $bill->對照表 ?? [];

$diff = LawDiffHelper::lawDiff($bill);
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
<?php if (empty($compare)) { ?>
  <div class="container my-3 p-3 bg-danger rounded-3">
    <span class="fs-5">無法律對照表</span>
  </div>
<?php } else { ?>
  <style>
  del {
    background-color: #fbb;
  }
  ins {
    background-color: #d4fcbc;
  }
  .reason-bg {
    background-color: #faf6f0;
  }
  </style>
  <div class="container my-3">
    <p class="mt-2 mb-0 fs-4 fw-bold">法律對照表</p>
  </div>
  <?php foreach($diff as $law_article_idx => $commit) { ?>
    <div class="container my-3">
      <h2 class="fs-5"><?= $this->escape($law_article_idx) ?></h2>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th class="text-center" style="width: 15%">版本名稱</th>
            <th class="text-center">條文內容</th>
          </tr>
        </thead>
        <?php
        $current = (isset($commit->current)) ? $commit->current : '本條新增無現行版本';
        $reason = $commit->reason;
        $commit = $commit->diff ?? $commit->commit;
        ?>
        <tbody>
          <tr>
            <td class="px-3">現行條文</td>
            <td><?= nl2br($this->escape($current)) ?></td>
          </tr>
          <tr>
            <td class="px-3"><?= $this->escape($bill->{'提案單位/提案委員'} ?? '')?></td>
            <td>
              <div><?= nl2br(strip_tags($commit, '<del><ins>')) ?></div>
              <?php if (isset($reason)) { ?>
                <div class="m-3 p-3 reason-bg rounded-2"><?= nl2br($this->escape($reason)) ?></div>
              <?php } ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php } ?>
<?php } ?>
<?= $this->partial('common/footer') ?>
