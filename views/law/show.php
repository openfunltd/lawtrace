<?php
$law_id = $this->law_id;
$res = LYAPI::apiQuery("/law/{$law_id}" ,"查詢法律編號：{$law_id} ");
$law = $res->data ?? new stdClass();
?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold display-6"><?= $this->escape($law->名稱 ?? '') ?></h1>
    </div>
  </div>
</div>
<?= $this->partial('common/footer') ?>
