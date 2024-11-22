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
<?php foreach($laws as $law) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-top-2">
      <div class="col-10 m-0">
        <h2 class="h4 mt-4 mb-0">
          <?php
          $law_name_highlights = $law->{'名稱:highlight'} ?? [];
          if (empty($law_name_highlights)) {
              echo $this->escape($law->名稱);
          } else {
              echo nl2br(strip_tags($law->{'名稱:highlight'}[0], '<em>'));
          }
          ?>
        </h2>
        <?php if ($aliases = $law->其他名稱) { ?>
          <p class="mt-3 mb-0">
            別名：
            <?= $this->escape(implode('、', $aliases)) ?>
          </p>
        <?php } ?>
        <p class="mt-1 mb-0"><?= $this->escape($law->最新版本->版本編號 ?? '') ?><p>
      </div>
      <div class="col-2 d-flex justify-content-center align-items-center">
        <a href="/law/show/<?= $this->escape($law->法律編號) ?>">
          <span class="material-symbols-outlined display-4">arrow_forward</span>
        </a>
      </div>
    </div>
    <?php
    $res = LYAPI::apiQuery("/law_contents?q=\"{$q}\"&法律編號={$law->法律編號}&limit=5", "查詢 {$law->名稱}({$law->法律編號}) 的法條 關鍵字：{$q}");
    $law_contents = $res->lawcontents ?? [];
    ?>
    <?php if (!empty($law_contents)) { ?>
      <div class="row border border-top-0 px-5 bg-light">
        <div class="col">
          <p class="h5 m-1 py-1">法條內容結果</p>
        </div>
      </div>
      <div class="row border border-top-0 px-5 rounded-bottom-2">
        <div class="col">
          <table class="table table-sm mt-1 ms-3">
            <tbody>
              <?php foreach ($law_contents as $law_content) { ?>
                <tr>
                  <td style="width: 14%;"><?= $this->escape($law_content->條號?? '') ?></td>
                  <td><?= nl2br(strip_tags($law_content->{'內容:highlight'}[0], '<em>')) ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>
  </div>
<?php } ?>
<?= $this->partial('common/footer') ?>
