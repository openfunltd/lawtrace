<?php
$law = $this->law;
$q = $this->q;
?>
<div class="container my-3">
  <div class="row border px-5 py-0 rounded-top-2">
    <div class="col m-0">
      <h2 class="h4 mt-3 mb-0"><?= nl2br(strip_tags($law->{'名稱:highlight'}[0], '<em>')) ?></h2>
      <?php if ($aliases = $law->其他名稱) { ?>
        <p class="mt-3 mb-0">
          別名：
          <?= $this->escape(implode('、', $aliases)) ?>
        </p>
      <?php } ?>
      <p class="mt-1 mb-0"><?= $this->escape($law->最新版本->版本編號 ?? '') ?><p>
    </div>
  </div>
  <?php
  $res = LYAPI::apiQuery("/law_contents?q=\"{$q}\"&法律編號={$law->法律編號}&limit=10", "查詢 {$law->名稱}({$law->法律編號}) 的法條 關鍵字：{$q}");
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
