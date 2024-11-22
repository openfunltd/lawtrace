<?php
$q = $this->q;
$res = LYAPI::apiQuery("/laws?q=\"{$q}\"&limit=100", "查詢 laws 關鍵字：{$q}");
$laws = $res->laws;
?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<style>
  em {
    font-style: normal;
    color: red;
  }
</style>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <p class="display-6">LawTrace 進階搜尋</p>
      <form action="/search" method="get">
        <div class="input-group">
          <span class="input-group-text material-symbols-outlined">search</span>
          <input type="text" class="form-control" placeholder="請輸入關鍵字" name="q" required>
        </div>
      </form>
      <div class="mt-2 fs-4">
        <span class="badge rounded-pill text-bg-primary fw-normal">關鍵字：<?= $this->escape($q) ?></span>
      </div>
    </div>
  </div>
</div>
<?php
foreach ($laws as $law) {
    echo $this->partial('partial/search_result', ['law' => $law, 'q' => $q]);
}
?>
<?= $this->partial('common/footer') ?>
