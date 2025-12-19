<?php
$version_id_input = $this->version_id_input;
$versions_in_terms = $this->versions_data->versions_in_terms;
$version_id_selected = $this->versions_data->version_id_selected;
$version_warning = $this->versions_data->warning ?? false;

$is_third_read_history = (strpos($version_id_selected, 'progress') === false);
$is_progress_history = (strpos($version_id_selected, 'progress') !== false);
$this->tab = 'history';
if (strpos($version_id_input, "-progress")) {
    $this->progress_term = substr($version_id_input, 6, strpos($version_id_input, "-progress"));
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
                            <?php
                            if ($version->版本編號 == $version_id_selected) {
                                $term_selected = $term;
                            }
                            $history_url = "/law/history/{$this->law_id}?version={$version->版本編號}";
                            if (property_exists($version, '動作')) {
                                $history_url .= "&source=version:{$version->法律編號}:{$version->日期}";
                            }
                            ?>
                            <a href="<?= $this->escape($history_url) ?>">
                              <?php if (property_exists($version, '動作')) { ?>
                              <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                              <?php } else { ?>
                              <?= ($is_current_term ? '待審議案' : '過期議案')?>
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
            <?php if ($term_selected == 0) { ?>
            <div class="alert alert-primary" role="alert">
              <i class="bi bi-exclamation-triangle-fill"></i>
              在<a href="https://lis.ly.gov.tw/lglawc/lglawkm">立法院法律系統</a>立法沿革中有收錄立法院設立前的三讀版本條文，但無收錄立法歷程。第 0 屆係指立法院設立前所制定的法律。立法院於民國 17 年設立，在此之前最高立法機關為中華民國國會。
            </div>
            <?php } ?>
            <?php if ($is_third_read_history or $this->single_version) { ?>
              <?php if ('history-from-progress' == $version_warning) { ?>  
                <div class="alert alert-primary" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    因 <a href="https://www.ly.gov.tw/Pages/ashx/LawRedirect.ashx?CODE=<?= $this->law_id ?>" target="_blank">立法院法律系統</a> 尚未提供本次三讀的立法歷程資料，本頁過程為程式推算歷程，可能與實際情況有所出入。<a href="https://docs.google.com/document/d/1OtTTnLCXa8FbsQBPFXmtPmXWvhaSW_n8LxcBnE1AYbA/edit?tab=t.0" target="_blank">了解更多</a>
                </div>
              <?php } ?>
              <?= $this->partial('partial/law_history_timeline', ['history_groups' => $this->history_groups]) ?>
            <?php } elseif ($is_progress_history) { ?>
              <div class="alert alert-primary" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                本頁資料只包含來自「立法院法律系統」的經歷過程、「立法院議事暨公報資訊網」的相關會議頁面、「立法院議事及發言系統」的三讀狀態、及「公共政策網路參與平臺」的眾開講法令草案預告，並於每日更新。
              </div>
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
                    <?php $id = $bill->bill_id ?? $bill->policy_uid ?? ''; ?>
                    <div id="<?= $this->escape($id) ?>" class="version-section-bar">
                      <div class="title">
                        <?php if (property_exists($bill, 'party_img_path')) { ?>
                        <img class="me-1 mb-1" width="16" height="16" src="<?= $bill->party_img_path ?>">
                        <?php } ?>
                        <?= $this->escape($bill->主提案 ?? $bill->proposers_str) ?>版本
                        <small><?= $this->escape($bill->會議民國日期v2) ?>提案</small>
                        <?php if ($bill->withdraw_status) { ?>
                          （<?= $this->escape($bill->withdraw_status) ?>）
                        <?php } ?>
                      </div>
                      <div class="actions">
                        <?php if ($bill->compare_url) { ?>
                        <a href="<?= $this->escape($bill->compare_url) ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi-arrow-right"></i>
                          比較議案
                        </a>
                        <?php } ?>
                        <?php $source_url = $bill->review_ppg_url ?? $bill->policy_url ?? null ?>
                        <?php if ($source_url) { ?>
                        <a href="<?= $this->escape($source_url) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                          <i class="bi-box-arrow-up-right"></i>
                          原始資料
                        </a>
                        <?php } ?>
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
