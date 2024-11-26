<?php
$law_content_id = $this->law_content_id;

$res = LYAPI::apiQuery("/law_content/" . urlencode($law_content_id) ,"查詢單一法條 法條編號：{$law_content_id} ");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law_content data with law_content_id {$law_content_id}</p>";
    exit;
}
//TODO 如果是章節名稱，回傳 404
$law_content = $res->data;

$parts = explode(":", $law_content_id);
$law_id =  $parts[0];
$version_id = $parts[1] . ":" . $parts[2];
$version_name = $parts[2];
$law_content_order = $parts[3];

$url = sprintf('/law_contents?法律編號=%s&順序=%s', urlencode($law_id), urlencode($law_content_order));
$res = LYAPI::apiQuery($url, "查詢不同版本的條文 法律編號: {$law_id}, 順序: {$law_content_order}");
$law_content_cnt = $res->total;
$law_contents = $res->lawcontents;
//law_contents order by id DESC
usort($law_contents, function($c1, $c2) {
    $id_c1 = $c1->版本編號 ?? '';
    $id_c2 = $c2->版本編號 ?? '';
    return $id_c2 <=> $id_c1;
});

?>
<?= $this->partial('common/header', ['title' => '單一法條']) ?>
<div class="container bg-light bg-gradient mt-5 mb-3 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <h1 class="fw-bold display-6">
        <?= $this->escape($law_content->{'法律編號:str'} ?? '') ?>
        <?= $this->escape($law_content->條號 ?? '') ?>
      </h1>
      <p class="badge text-bg-primary fw-normal m-2 p-2 fs-6">版本：<?= $this->escape($version_name) ?></p>
    </div>
  </div>
</div>
<div class="container my-3">
  <a
    href="/law/show/<?= $this->escape($law_id)?>?version=<?= $this->escape($version_id)?>"
    class="btn btn-primary"
  >
    回完整條文
  </a>
</div>
<?php if (isset($law_content)) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-2">
      <h3 class="py-3 fs-5">條文內容</h3>
      <p class="p-2 fs-5"><?= nl2br($this->escape($law_content->內容 ?? '')) ?></p>
      <div class="bg-warning-subtle m-3 p-3 rounded-3">
        <h3 class="py-3 fs-5">立法理由</h3>
        <p class="p-2 fs-5"><?= nl2br($this->escape($law_content->立法理由 ?? '')) ?></p>
      </div>
    </div>
  </div>
<?php } ?>
<?php if ($law_content_cnt > 0) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-2">
      <h3 class="py-3 fs-5">版本歷程</h3>
      <table class="table fs-6">
        <tbody>
          <?php foreach ($law_contents as $content) { ?>
            <?php
            $version_id = $content->版本編號 ?? '';
            $version_name = explode(':', $version_id)[1] ?? '';
            ?>
            <tr>
              <td style="width: 15%;"><?= $this->escape($version_name) ?></td>
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
