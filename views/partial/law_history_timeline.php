<?php
$history_groups = $this->_data['history_groups'];
?>
<div class="timeline">
  <?php foreach ($history_groups[0]->timeline as $timeline_node) { ?>
    <div class="timeline-item">
      <div class="item-head">
        <span class="title"><?= $this->escape($timeline_node->進度) ?></span>
        <small><?= $this->escape($timeline_node->會議民國日期) ?></small>
        <?php if ($timeline_node->items[0]->is_meet) { ?>
          <a href="<?= $this->escape($timeline_node->items[0]->ppg_url) ?>" target="_blank">
            原始資料
            <i class="bi bi-box-arrow-up-right"></i>
          </a>
        <?php } ?>
      </div>
      <?php if ($timeline_node->進度 == '一讀') {?>
        <div class="item-body">
          <div class="history-grid">
            <div class="grid-head">
              相關議案及其提案之條文 (共 <?= count($timeline_node->items) ?> 案)
              <i class="bi bi-chevron-up icon"></i>
            </div>
            <div class="grid-body">
              <?php foreach ($timeline_node->items as $history) { ?>
                <div class="grid-row">
                  <div class="party-img">
                    <?php if (property_exists($history, 'party_img_path')) { ?>
                      <img width="16" height="16" src="<?= $history->party_img_path ?>">
                    <?php } ?>
                  </div>
                  <div class="party"><?= $this->escape($history->proposers_str ?? $history->主提案) ?></div>
                  <?php if (property_exists($history, 'article_numbers')) { ?>
                    <div class="sections">第 <?= implode(', ', ($history->article_numbers)) ?> 條</div>
                  <?php } ?>
                  <?php if (property_exists($history, 'ppg_url')) { ?>
                    <div class="details">
                      <a href="<?= $this->escape($history->compare_url)?>" target="_blank">
                        議案詳細資訊
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  <?php } elseif (property_exists($history, 'related_doc_url')) { ?>
                    <div class="details">
                      <a href="<?= $this->escape($history->related_doc_url)?>" target="_blank">
                        議案關係文書
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  <?php } ?>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
      <?php } elseif ($timeline_node->items[0]->is_meet or $timeline_node->items[0]->is_incidental_resolution) { ?>
        <div class="item-body">
          <?php $history = $timeline_node->items[0]; ?>
          <?php if ($history->convener) { ?>
            <div class="history-card">
              <div class="card-left">
                召集人
              </div>
              <div class="card-right">
                <img width="16" height="16" src="<?= $this->escape($history->convener_party_img_path) ?>">
                <?= $this->escape($history->convener) ?>
              </div>
            </div>
          <?php } ?>
          <?php if ($history->meet_committees) { ?>
            <div class="history-card">
              <div class="card-left">
                委員會
              </div>
              <div class="card-right">
                <?= nl2br($this->escape(implode("\n", $history->meet_committees))) ?>
              </div>
            </div>
          <?php } ?>
          <?php if ($history->review_report_doc) { ?>
            <div class="history-card">
              <div class="card-left">審查報告</div>
              <div class="card-right">
                <a href="<?= $this->escape($history->review_report_doc) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  下載
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </div>
          <?php } ?>
          <?php if (isset($history->gazette_ppg_url)) { ?>
            <div class="history-card">
              <div class="card-left">
                公報
              </div>
              <div class="card-right">
               <a class="btn btn-sm btn-outline-primary"
                  href="<?= $this->escape($history->gazette_ppg_url) ?>" target="_blank">
                  原始資料
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <div class="hostory-rec">
                  相關紀錄位置：<?= $this->escape($history->立法紀錄) ?>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  <?php } ?>
</div>
