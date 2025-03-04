<?php
$version_id_input = $this->version_id_input;
$versions_in_terms = $this->versions_data->versions_in_terms;
$version_id_selected = $this->versions_data->version_id_selected;

$is_third_read_history = (strpos($version_id_selected, 'progress') === false);
$is_progress_history = (strpos($version_id_selected, 'progress') !== false);
$this->tab = 'history';
if (strpos($version_id_input, "-progress")) {
    $this->source_type = 'progress';
    $this->progress_term = substr($version_id_input, 6, strpos($version_id_input, "-progress"));
} else {
    $this->source_type = 'version';
}
?>
<?php $law_name = $this->escape($this->law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 經歷過程", 'body_class' => 'law-history-page']) ?>
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
                <?php foreach ($versions_in_terms as $term => $versions) { ?>
                  <div class="menu-item level-1">
                    <div class="menu-head">
                      <?php if ($is_current_term) { ?>
                        第<?= $term ?>屆 (目前屆期)
                      <?php } else { ?>
                        第<?= $term ?>屆
                      <?php } ?>
                      <i class="bi icon bi-chevron-up"></i>
                    </div>
                    <div class="menu-body">
                      <?php foreach ($versions as $version) { ?>
                        <div class="menu-item level-3">
                          <div class="menu-head <?= ($version->版本編號 == $version_id_selected) ? 'active' : '' ?>">
                            <a href="/law/history/<?= $this->escape($this->law_id) ?>?version=<?= $this->escape($version->版本編號) ?>">
                              <?php if (property_exists($version, '動作')) { ?>
                                <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                              <?php } else { ?>
                                未議決議案
                              <?php } ?>
                            </a>
                          </div>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                  <?php $is_current_term = false; ?>
                <?php } ?>
              </div>
            </div>
          </div>
          <div>
            <?php if ($is_third_read_history) { ?>
              <?= $this->partial('partial/law_history_timeline', ['history_groups' => $this->history_groups]) ?>
            <?php } ?>
            <?php if ($is_progress_history) { ?>
              <?= $this->partial('partial/law_history_menu', ['history_groups' => $this->history_groups]) ?>
              <?php foreach ($this->history_groups as $history_group) { ?>
                <?php if ($history_group->id != '未分類') { ?>
                  <div id="<?= $this->escape($history_group->id) ?>" class="version-section-bar">
                    <div class="title">
                      <?= $this->escape($history_group->group_title) ?>
                      <small><?= $this->escape($history_group->review_date) ?></small>
                    </div>
                    <div class="actions">
                      <a href="<?= $this->escape($history_group->compare_url) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi-arrow-right"></i>
                        比較議案
                      </a>
                      <a href="<?= $this->escape($history_group->review_ppg_url) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="bi-box-arrow-up-right"></i>
                        原始資料
                      </a>
                    </div>
                  </div>
                  <?= $this->partial('partial/law_history_timeline', ['history_groups' => $this->history_groups]) ?>
                <?php } else { ?>
                  <?php foreach ($history_group->bill_log as $bill) { ?>
                    <div id="<?= $this->escape($bill->bill_id) ?>" class="version-section-bar">
                      <div class="title">
                        <?= $this->escape($bill->主提案) ?>版本
                        <small><?= $this->escape($bill->會議民國日期v2) ?>提案</small>
                      </div>
                      <div class="actions">
                        <a href="<?= $this->escape($bill->compare_url) ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi-arrow-right"></i>
                          比較議案
                        </a>
                        <a href="<?= $this->escape($bill->review_ppg_url) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                          <i class="bi-box-arrow-up-right"></i>
                          原始資料
                        </a>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
