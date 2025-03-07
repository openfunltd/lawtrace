<?php
$law_id = $this->law_id;
$version_id_input = $this->version_id_input;
$this->tab = 'show';
$this->source_type = 'version';

$is_draft = is_null($this->versions_data); //還在草案階段的

$chapters = array_filter($this->contents, function($content) {
    $chapter_name = $content->章名 ?? '';
    $chapter_unit = ($chapter_name != '') ? LawChapterHelper::getChapterUnit($chapter_name) : '';

    //要剔除把法律名稱又放進去章名的狀況 example: 民法第二編 債 law_id:04509
    return !in_array($chapter_unit, ['','法']);
});

//deal with edge case 總統副總統選舉罷免法(law_id:04318)
//chapter_name '第四章 （刪除） 第九節 罷免' => '第九節 罷免';
foreach ($chapters as $chapter) {
    $chapter_name = $chapter->章名;
    $has_deletion = (mb_strpos($chapter_name, '刪除') !== false);
    if ($has_deletion) {
        $chapter_name_after_deletion = mb_substr($chapter_name, mb_strpos($chapter_name, '刪除') + 2);
        $has_other_chapter = (mb_strpos($chapter_name_after_deletion, '第') !== false);
        if ($has_other_chapter) {
            $chapter_name = mb_substr($chapter_name_after_deletion, mb_strpos($chapter_name_after_deletion, '第'));
            $chapter->章名 = $chapter_name;
        }
    }
}

$chapter_units = LawChapterHelper::getChapterUnits($chapters);

?>
<?php $law_name = $this->escape($this->law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 瀏覽法律", 'body_class' => 'law-details-page']) ?>
<div class="main">
  <?= $this->partial('law/law_hero', $this) ?>
  <?php if ($is_draft) { ?>
    <div class="main-content">
      <section class="law-details">
        <div class="container">
          <div class="alert alert-primary" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
              本法處於草案階段，尚未有任何三讀的法律版本供瀏覽。想了解草案的討論過程，請點選「經歷過程」查閱。
          </div>
        </div>
      </section>
    </div>
    </div>
    <?php exit; ?>
  <?php } ?>
  <div class="main-content">
    <section class="law-details">
      <div class="container">
        <div class="law-version">
          <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                版本：<?= $this->escape("{$this->versions_data->version_selected->民國日期} {$this->versions_data->version_selected->動作}") ?>
                <?= ($this->versions_data->version_selected->現行版本 == '現行') ? '(現行版本)' : '' ?>
            </button>
            <ul class="dropdown-menu">
              <?php foreach ($this->versions_data->versions as $version) { ?>
                <li>
                  <a
                    class="dropdown-item"
                    href="/law/show/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>"
                  >
                    <?= $this->escape("{$version->民國日期} {$version->動作}") ?>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </div>
        </div>
        <div class="law-list-wrapper">
          <?=
          $this->partial('partial/side',[
              'chapters' => $chapters,
              'chapter_units' =>$chapter_units,
          ])
          ?>
          <div class="law-list">
            <?php foreach ($this->contents as $content) { ?>
              <?php
              $content_order = $content->順序;
              $chapter_name = $content->章名 ?? '';
              $chapter_unit = ($chapter_name != '') ? LawChapterHelper::getChapterUnit($chapter_name) : '';
              $title_level = array_search($chapter_unit, $chapter_units);
              $law_index = $content->條號 ?? '';
              $law_content = $content->內容 ?? '';
              $law_content_id = $content->法條編號 ?? '';
              $law_reason = $content->立法理由 ?? '';
              ?>
              <?php if ($title_level !== false) { ?>
                <div
                  id="contentOrder-<?= $this->escape($content_order) ?>"
                  class="title-level-<?= $this->escape($title_level + 1) ?>"
                >
                  <?= $this->escape($chapter_name) ?>
                </div>
              <?php } ?>
              <?php if (!in_array($law_index, ['', '法律名稱'])) { ?>
                <div class="info-card">
                  <div class="card-head">
                    <div class="title">
                      <?= $this->escape($law_index) ?>
                    </div>
                    <div class="actions">
                      <div class="dropdown">
                        <span data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots-vertical"></i>
                        </span>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="/law/single/<?= $this->escape($law_content_id) ?>">
                              只顯示此法條內容
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <?php $law_content = mb_ereg_replace('　', '', $law_content); ?>
                    <?= nl2br($this->escape($law_content))?>
                    <?php if ($law_reason != '') { ?>
                      <div class="card-help">
                        <div class="help-title">
                          立法說明
                          <i class="bi bi-chevron-down icon"></i>
                        </div>
                        <div class="help-body">
                          <?php $law_reason = mb_ereg_replace('　', '', $law_reason); ?>
                          <?= nl2br($this->escape($law_reason))?>
                        </div>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
