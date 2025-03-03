<?php
$version_id_input = $this->version_id_input;
$this->tab = 'diff';
$this->source_type = 'version';
$this->version = $version_id_input;

$selected_version_law_name = $this->law_contents[0]->內容;
if (!is_null($this->law_contents_previous)) {
    $previous_law_name = $this->law_contents_previous[0]->內容;
}
$law_name_changed_flag = (isset($previous_law_name) and ($selected_version_law_name != $previous_law_name));

//filter contents, retrieve new modified contents in this version
$modified_contents = array_filter($this->law_contents, function($content) {
    return ($content->版本追蹤 == 'new');
});

$commit = [];
$html_patterns = [
    '<ins>' => '<span class="add">',
    '<\/ins>' => '</span>',
    '<del>' => '<span class="remove">',
    '<\/del>' => '</span>',
];
$amendment_idx = 0;
foreach ($modified_contents as $content) {
    $modification = new stdClass();
    $article_number = $content->條號;
    $modified_text = $content->內容;
    if (mb_strpos($modified_text, $article_number) === 0) {
        $modified_text = mb_substr($modified_text, mb_strlen($article_number) + 1);
    }
    $reason = $content->立法理由;

    $base_content = new stdClass();
    if (!is_null($this->law_contents_previous)) {
        foreach ($this->law_contents_previous as $previous_content) {
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
        $modification->amendment_idx = $amendment_idx;
        $amendment_idx++;
    }
    $modification->reason = $reason;
    $commit[] = $modification; 
}

$history_endpoint = "/law/history/{$this->law_id}";
if ($version_id_input != 'latest') {
    $history_endpoint = $history_endpoint . "?version={$version_id_input}";
}
$this->nav_link_history = $history_endpoint;
?>
<?php $law_name = $this->escape($this->law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 異動條文"]) ?>
<div class="main">
  <?= $this->partial('/law/law_hero', $this) ?>  
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
                <?php $is_current_term = true; ?>
                <?php foreach ($this->versions_data->versions_in_terms_filtered as $term => $versions) { ?>
                  <div class="menu-item level-1">
                    <div class="menu-head">
                      <?php if ($is_current_term) { ?>
                        第<?= $term ?>屆 (目前屆期)
                        <?php $is_current_term = false; ?>
                      <?php } else { ?>
                        第<?= $term ?>屆
                      <?php } ?>
                      <i class="bi icon bi-chevron-up"></i>
                    </div>
                    <div class="menu-body">
                      <?php foreach ($versions as $version) { ?>
                        <div class="menu-item level-3">
                          <div class="menu-head <?= ($version->版本編號 == $this->versions_data->version_id_selected) ? 'active' : '' ?>">
                            <a href="/law/diff/<?= $this->escape($this->law_id) ?>?version=<?= $this->escape($version->版本編號) ?>">
                              <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                            </a>
                          </div>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
          <div>
            <div class="law-diff-wrapper">
              <div class="diff-info">
                <span class="add">綠色</span>為新增 <span class="remove">紅色</span>為刪除
              </div>
            </div>

            <?php if ($law_name_changed_flag) { ?>
              <div class="law-diff-title">法律名稱</div>
              <div class="law-diff-row">
                <div class="info-card">
                  <div class="card-head">
                    <div class="title">
                      原名稱
                      <small>
                        <?= $this->escape($this->versions_data->version_previous->民國日期_format2 . ' ' . $this->versions_data->version_previous->動作 . '版本') ?>
                      </small>
                    </div>
                  </div>
                  <div class="card-body big"><?= $this->escape($previous_law_name) ?></div>
                </div>
                <div class="info-card">
                  <div class="card-head">
                    <div class="title">
                      <?= $this->escape($this->versions_data->version_selected->民國日期_format2 . ' ' . $this->versions_data->version_selected->動作 . '版本') ?>
                    </div>
                  </div>
                  <div class="card-body big law-name-diff"></div>
                </div>
              </div>
            <?php } ?>
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
                     <?= $this->escape($this->versions_data->version_previous->民國日期_format2 . ' ' . $this->versions_data->version_previous->動作 . '版本') ?>
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
                     <?= $this->escape($this->versions_data->version_selected->民國日期_format2 . ' ' . $this->versions_data->version_selected->動作 . '版本') ?>
                 </div>
               </div>
               <?php if (!property_exists($modification, 'amendment_idx')) { ?>
                 <div class="card-body">
               <?php } else { ?>
                 <div class="card-body <?= 'amendment-' . $modification->amendment_idx?>">
               <?php } ?>
                 <?php if ($modification->type == 'addition') { ?>
                   <?php $modified_text = mb_ereg_replace('　', '', $modification->modified_text); ?>
                   <span class="add"><?= nl2br($this->escape($modified_text)) ?></span>
                 <?php } elseif ($modification->type == 'deletion') { ?>
                   <?php $modified_text = mb_ereg_replace('　', '', $modification->modified_text); ?>
                   <span class="remove-all"><?= nl2br($this->escape($modified_text)) ?></span>
                 <?php } ?>
               </div>
               <?php $reason = mb_ereg_replace('　', '', $modification->reason); ?>
               <?php if (trim($reason) != '') { ?>
                 <div class="card-help">
                   <div class="help-title">
                     說明
                     <i class="bi bi-chevron-down icon"></i>
                   </div>
                   <div class="help-body">
                     <?= nl2br($this->escape($reason)) ?>
                   </div>
                 </div>
               <?php } ?>
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
<?php
$commit = array_filter($commit, function($modification) {
    return property_exists($modification, 'amendment_idx');
});
?>
<script
  type="module"
  integrity="sha384-OBDIiiw8eyL4gibOdkiy41jBwG6oslrlO4W6aKvgB4b+NP8iIhZ4mW1IOwPGlEhO"
  crossorigin="anonymous"
>
  import Diff from 'https://cdn.jsdelivr.net/npm/text-diff@1.0.1/+esm';
  window.Diff = Diff;
</script>
<script>
  function swapHtml(diffHtml){
    const htmlPatterns = {
      '<ins>': '<span class="add">',
      '</ins>': '</span>',
      '<del>': '<span class="remove">',
      '</del>': '</span>',
    };
    for (const pattern in htmlPatterns) {
      const replacement = htmlPatterns[pattern];
      const regex = new RegExp(pattern, 'g');
      diffHtml = diffHtml.replace(regex, replacement);
    }
    return diffHtml;
  }

  window.onload = function(){
    const commit = <?= json_encode($commit) ?>;
    for (const [idx, modification] of Object.entries(commit)) {
      const targetClass = 'amendment-' + modification['amendment_idx'];
      const diff = new Diff();
      const textDiff = diff.main(modification['base_text'], modification['modified_text']);
      let diffHtml = diff.prettyHtml(textDiff);
      diffHtml = swapHtml(diffHtml);
      diffHtml = diffHtml.replace(/　/g, '');
      $('.card-body.' + targetClass).html(diffHtml);
    }
    <?php if ($law_name_changed_flag) { ?>
      const previousLawName = '<?= $this->escape($previous_law_name) ?>';
      const selectedVersionLawName = '<?= $this->escape($selected_version_law_name) ?>';
      const diff = new Diff();
      const lawNameDiff = diff.main(previousLawName, selectedVersionLawName);
      let lawNameDiffHtml = diff.prettyHtml(lawNameDiff);
      lawNameDiffHtml = swapHtml(lawNameDiffHtml);
      $('.law-name-diff').html(lawNameDiffHtml);
    <?php } ?>
  }

</script>
