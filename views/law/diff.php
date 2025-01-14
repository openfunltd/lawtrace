<?php
use cogpowered\FineDiff\Diff;

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

$versions_data = LawVersionHelper::getVersions($law_id, $version_id_input);
$versions = $versions_data->versions;
$version_selected = $versions_data->version_selected;
$version_previous = $versions_data->version_previous;
$version_id_selected = $versions_data->version_id_selected;
$version_id_previous = $versions_data->version_id_previous;
if (is_null($version_selected)) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No version data with version_id {$version_id_input}</p>";
    exit;
}

$res = LYAPI::apiQuery(
    "/law_version/{$version_id_selected}/contents",
    "查詢版本條文 版本：{$version_id_selected}"
);
$res_total = $res->total ?? 0;
if ($res_total == 0) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No law_conetnts with law_version_id {$version_id_selected}</p>";
    exit;
}
$law_contents = $res->lawcontents;

if (!is_null($version_id_previous)) {
    $res = LYAPI::apiQuery(
        "/law_version/{$version_id_previous}/contents",
        "查詢上一個版本條文 版本：{$version_id_previous}"
    );
    if ($res_total == 0) {
        header('HTTP/1.1 404 No Found');
        echo "<h1>404 No Found</h1>";
        echo "<p>No law_conetnts with previous law_version_id {$version_id_previous}</p>";
        exit;
    }
    $law_contents_previous = $res->lawcontents;
}

//filter contents, retrieve new modified contents in this version
$modified_contents = array_filter($law_contents, function($content) {
    return ($content->版本追蹤 == 'new');
});

$commit = [];
$fine_diff = new Diff();
$html_patterns = [
    '<ins>' => '<span class="add">',
    '<\/ins>' => '</span>',
    '<del>' => '<span class="remove">',
    '<\/del>' => '</span>',
];
foreach ($modified_contents as $content) {
    $modification = new stdClass();
    $article_number = $content->條號;
    $modified_text = $content->內容;
    if (mb_strpos($modified_text, $article_number) === 0) {
        $modified_text = mb_substr($modified_text, mb_strlen($article_number) + 1);
    }
    $reason = $content->立法理由;

    $base_content = new stdClass();
    if (!is_null($law_contents_previous)) {
        foreach ($law_contents_previous as $previous_content) {
            $previous_article_number = $previous_content->條號;
            if ($previous_article_number == $article_number) {
                $base_content = $previous_content;
                break;
            }
        }
    }

    //determin type is amendment, addition or deletion
    if (empty((array) $base_content)) {
        $type = 'addition';
    } else {
        if (mb_strpos($reason, '本條刪除') !== false or (mb_strpos($reason, '刪除') !== false and mb_strlen($modified_text) <= 6)) {
            $type = 'deletion';
        } else {
            $type = 'amendment';
        }
    }

    $modification->type = $type;
    $modification->modified_text = $modified_text;
    if (!empty((array) $base_content)) {
        $base_text = $base_content->內容;
        if (mb_strpos($base_text, $article_number) === 0) {
            $base_text = mb_substr($base_text, mb_strlen($article_number) + 1);
        }
        $modification->base_text = $base_text;
    }
    $article_number = mb_ereg_replace('[ 　]', '', $article_number); //remove 全形與半形空白
    $modification->article_number = $article_number;
    if ($type == 'amendment') {
        $diff_html = $fine_diff->render($base_text, $modified_text);
        $diff_html = preg_replace('/\\\\n/', "\n", $diff_html);
        foreach ($html_patterns as $pattern => $replacement) {
            $diff_html = mb_ereg_replace($pattern, $replacement, $diff_html);
        }
        $modification->diff_html = $diff_html;
    }
    $modification->reason = $reason;
    $commit[] = $modification; 
}

$aliases = $law->其他名稱 ?? [];
$vernaculars = $law->別名 ?? [];
$show_endpoint = "/law/show/{$law_id}";
$history_endpoint = "/law/history/{$law_id}";
if ($version_id_input != 'latest') {
    $show_endpoint = $show_endpoint . "?version={$version_id_input}";
    $history_endpoint = $history_endpoint . "?version={$version_id_input}";
}
?>
<?= $this->partial('common/header', ['title' => '異動條文']) ?>
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
          <li class="breadcrumb-item active">
            法律資訊
          </li>
        </ol>
      </nav>
      <h2 class="light">
        <?= $this->escape($law->名稱 ?? '') ?>
      </h2>
      <div class="info">
        <?php if (!empty($aliases)) { ?>
          <div class="alias">
            別名：<?= $this->escape(implode('、', $aliases)) ?>
          </div>
        <?php } ?>
        <?php if (!empty($vernaculars)) { ?>
          <div class="vernacular">
            俗名：<?= $this->escape(implode('、', $vernaculars)) ?>
          </div>
        <?php } ?>
      </div>
      <div class="btn-group law-pages">
        <a href="<?= $this->escape($show_endpoint) ?>" class="btn btn-outline-primary">
          瀏覽法律
        </a>
        <a href="#" class="btn btn-outline-primary active">
          查看修訂歷程
        </a>
      </div>
    </div>
  </section>
  <div class="main-content">
    <section class="law-details">
      <div class="container">
        <div class="law-list-wrapper">
          <div class="side">
            <div class="law-sections">
              <div class="title">
                選擇版本
              </div>
              <div class="side-menu version-menu">
                <?php foreach ($versions as $version) { ?>
                  <div class="menu-item level-1">
                    <?php if ($version->版本編號 == $version_id_selected) {?>
                      <div class="menu-head active">
                    <?php } else {?>
                      <div class="menu-head">
                    <?php }?>
                      <a href="/law/diff/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>">
                        <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                      </a>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
          <div>
            <ul class="nav nav-tabs">
              <li class="nav-item">
                <a class="nav-link active" href="#">異動條文</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?= $this->escape($history_endpoint) ?>">經歷過程</a>
              </li>
            </ul>

            <div class="law-diff-wrapper">
              <div class="diff-info">
                <span class="add">綠色</span>為新增 <span class="remove">紅色</span>為刪除
              </div>
            </div>

           <?php foreach ($commit as $modification) { ?>
           <div class="law-diff-title">
             <?= $this->escape($modification->article_number) ?>
           </div>
           <div class="law-diff-row">
             <?php if (in_array($modification->type, ['amendment', 'deletion'])) { ?>
             <div class="info-card">
               <div class="card-head">
                 <div class="title">
                   原條文
                   <small>
                     <?= $this->escape($version_previous->民國日期_format2 . ' ' . $version_previous->動作 . '版本') ?>
                   </small>
                 </div>
               </div>
               <div class="card-body">
                 <?php $base_text = mb_ereg_replace('　', '', $modification->base_text); ?>
                 <?= nl2br($this->escape($base_text)) ?>
               </div>
             </div>
             <?php } elseif ($modification->type == 'addition') { ?>
               <div class="info-card disabled">
                 <div class="card-body">
                   此條文為新增條文，無原條文可比對。
                 </div>
               </div>
             <?php } ?>
             <div class="info-card">
               <div class="card-head">
                 <div class="title">
                   <?= $this->escape($version_selected->民國日期_format2 . ' ' . $version_selected->動作 . '版本') ?>
                 </div>
               </div>
               <div class="card-body">
                 <?php if ($modification->type == 'amendment') { ?>
                    <?php $diff_html = mb_ereg_replace('　', '', $modification->diff_html); ?>
                    <?= nl2br($diff_html) ?>
                 <?php } elseif ($modification->type == 'addition') { ?>
                   <?php $modified_text = mb_ereg_replace('　', '', $modification->modified_text); ?>
                   <span class="add"><?= nl2br($this->escape($modified_text)) ?></span>
                 <?php } elseif ($modification->type == 'deletion') { ?>
                   <?php $modified_text = mb_ereg_replace('　', '', $modification->modified_text); ?>
                   <span class="remove-all"><?= nl2br($this->escape($modified_text)) ?></span>
                 <?php } ?>
               </div>
               <div class="card-help">
                 <div class="help-title">
                   說明
                   <i class="bi bi-chevron-down icon"></i>
                 </div>
                 <div class="help-body">
                   <?php $reason = mb_ereg_replace('　', '', $modification->reason); ?>
                   <?= nl2br($this->escape($reason)) ?>
                 </div>
               </div>
             </div>
           </div>
           <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
