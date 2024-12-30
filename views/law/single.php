<?php
$law_content_id = $this->law_content_id;
$law_content_id = $this->escape($law_content_id);
$id_array = explode(':', $law_content_id);
$law_id = $id_array[0];

$res = LYAPI::apiQuery("/law_content/{$law_content_id}" ,"查詢法律條文：{$law_content_id} ");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law_content data with law_content_id {$law_content_id}</p>";
    exit;
}
$law_content = $res->data;

$res = LYAPI::apiQuery("/law/{$law_id}" ,"查詢法律編號：{$law_id} ");
$res_error = $res->error ?? true;
if ($res_error) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law data with law_id {$law_id}</p>";
    exit;
}
$law = $res->data;

$law_name = $law->名稱 ?? '';
$law_content_name = $law_content->條號 ?? '';
?>
<?= $this->partial('common/header', ['title' => '法律內容']) ?>
<div class="main">
  <section class="page-hero law-details-info">
    <div class="container">
      <nav class="breadcrumb-wrapper">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="index.html">
              <i class="bi bi-house-door"></i>
            </a>
          </li>
          <li class="breadcrumb-item">
            <a href="/law/show/<?= $law_id ?>">
              法律資訊
            </a>
          </li>
          <li class="breadcrumb-item active">
            單一條文
          </li>
        </ol>
      </nav>
      <h2 class="light">
        <?= $this->escape($law_name) . ' | ' . $this->escape($law_content_name)?>
      </h2>
    </div>
  </section>
</div>
<?= $this->partial('common/footer') ?>
