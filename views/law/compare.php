<?php
$source_input = $this->source_input;
$version_count = count($this->diff->versions);
?>
<?= $this->partial('common/header', ['title' => "比較議案／條文"]) ?>
    <div class="main">
      <?= $this->partial('law/law_hero', $this) ?>
      <div class="main-content">
        <section class="law-details">
          <div class="container">
            <div class="law-compare-wrapper">
              <div class="compare-range">
                <div class="range-setting">
                  <button class="btn btn-primary set-compare-target">
                    設定比較範圍
                    <i class="bi bi-pencil-fill ms-1"></i>
                  </button>
                </div>
                <div class="range-info">
                  <div>
                      比較版本：113/00/00 修正版本 － VS － 其他 6 個版本<?php // TODO ?>
                  </div>
                  <div>
                      條文範圍：第 2, 6, 92 條<?php // TODO ?>
                  </div>
                </div>
                <div class="options">
                  <div class="form-check form-switch expand-law-help">
                    <input class="form-check-input" type="checkbox" role="switch" id="expandLawHelp">
                    <label class="form-check-label" for="expandLawHelp">
                      展開所有立法說明
                    </label>
                  </div>
                  <div class="form-check form-switch show-category">
                    <input class="form-check-input" type="checkbox" role="switch" id="showCategory">
                    <label class="form-check-label" for="showCategory">
                      顯示條文目錄
                    </label>
                  </div>
                </div>
              </div>
              <div class="diff-info rwd-full">
                <label>
                  <input type="radio" class="form-check-input" name="diff_by" checked>
                  顯示原文
                </label>
                <label>
                  <input type="radio" class="form-check-input" name="diff_by">
                  顯示<span class="add">新增</span>
                </label>
                <label>
                  <input type="radio" class="form-check-input" name="diff_by">
                  顯示<span class="add">新增</span>及<span class="remove">刪減</span>
                </label>
              </div>

              <div class="law-sections-wrapper">
                <div class="law-sections">
                  <div class="title">
                    選擇章節
                  </div>
                  <div class="side-menu">
                    <div class="menu-item">
                      <?php foreach ($this->diff->rule_diffs as $idx => $rule_diff) { ?>
                      <div class="menu-head">
                        <a href="#section-<?= $idx ?>1">
                          <?= $this->escape($rule_diff->條文) ?>
                        </a>
                      </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="law-diff-row-wrapper">
                <!--
                  ** 這邊請後端將--col-count帶入column數量 **
                  ** 如果沒有選擇比較對象，要填2（最小就是2） **
                -->
                <div class="law-diff-row law-diff-header-row" style="--col-count: <?= $version_count ?>;">
                  <!-- 比較基準 & 比較對象 -->
                  <div class="original compare-head">
                    比較基準
                  </div>
                  <div class="compare-head">
                    比較對象
                  </div>
                  <!-- 如果大於2時，要以空白div補足數量 -->
                  <?php for ($i = 0; $i < $version_count - 2; $i ++) { ?>
                  <div class="compare-head"></div><!-- 用空白div的數量補足column數量 -->
                  <?php } ?>

                  <!-- content-section-head -->
                  <?php foreach ($this->diff->versions as $version) { ?>
                  <div class="law-diff-head <?= $version->title == '現行版本' ? 'original' : '' ?>">
                    <div class="title">
                        <?= $this->escape($version->title) ?>
                        <small>
                            <?= $this->escape($version->subtitle) ?>
                        </small>
                    </div>
                    <div class="action">
                      <div class="dropdown">
                        <span data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots-vertical"></i>
                        </span>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                          <li>
                            <a class="dropdown-item" href="law_details_single.html">
                              <i class="bi bi-box-arrow-up-right"></i>
                              查看原始資料
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                </div>
                <?php } ?>
              </div>

              <!--
                ** 這邊請後端將--col-count帶入column數量 **
                ** 如果沒有選擇比較對象，要填2（最小就是2） **
              -->
              <div class="law-diff-row" style="--col-count: <?= $version_count ?>;">
                <?php foreach ($this->diff->rule_diffs as $idx => $rule_diff) { ?>  
                  <!-- content-section-title -->
                  <div class="original law-diff-title">
                      <?= $this->escape($rule_diff->條文) ?>
                  </div>
                  <?php for ($i = 0; $i < $version_count - 1; $i ++) { ?>
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <?php } ?>

                  <!-- content-section 1 -->
                  <?php foreach ($this->diff->versions as $version) { ?>
                    <div class="<?= $this->if($version->id == '現行版本', 'original', '') ?> law-diff-content">
                      <?= nl2br($rule_diff->versions->{$version->id}->內容 ?? '') ?>
                    </div>
                  <?php } ?>
                <?php } ?>
                </div>

              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="modal compare-target-modal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h6 class="modal-title">
                設定比較範圍：兒童及少年性剝削防制條例
              </h6>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="compare-row">
                <div class="compare-base">
                  <div class="title">
                    選擇基準
                  </div>
                  <div class="dropdown-select">
                    <div class="selected-item">
                      113/12/12修正通過（現行條文）
                      <i class="bi icon bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu select-list">
                      <input type="text" placeholder="搜尋" class="form-control filter-input">
                      <div class="scroller">
                        <div class="dropdown-item disabled group-label">
                          第Ｏ屆
                        </div>
                        <span class="dropdown-item">
                          113/12/12修正通過(現行)
                        </span>
                        <span class="dropdown-item">
                          113/00/00 三讀版本
                        </span>
                        <span class="dropdown-item">
                          113/00/00 行政院版本
                        </span>
                        <div class="dropdown-item disabled group-label">
                          第Ｏ屆
                        </div>
                        <span class="dropdown-item">
                          113/00/00 修正通過
                        </span>
                        <span class="dropdown-item">
                          113/00/00 三讀版本
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="compare-target">
                  <div class="title">
                    比較對象
                  </div>
                  <div class="dropdown-select">
                    <div class="selected-item">
                      請選擇比較對象 (6)
                      <i class="bi icon bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu select-list">
                      <input type="text" placeholder="搜尋" class="form-control filter-input">
                      <div class="scroller">
                        <div class="dropdown-item disabled group-label">
                          關聯議案
                        </div>
                        <div class="dropdown-item">
                          <input type="checkbox">
                          劉建國等16人｜113/00/00 提案版本
                        </div>
                        <div class="dropdown-item">
                          <input type="checkbox">
                          劉建國等16人｜113/00/00 提案版本
                        </div>
                        <div class="dropdown-item">
                          <input type="checkbox">
                          劉建國等16人｜113/00/00 提案版本
                        </div>
                        <div class="dropdown-item disabled group-label">
                          其他議案
                        </div>
                        <div class="dropdown-item disabled group-label">
                          － 第Ｏ屆
                        </div>
                        <div class="dropdown-item">
                          <input type="checkbox">
                          劉建國等16人｜113/00/00 提案版本
                        </div>
                        <div class="dropdown-item">
                          <input type="checkbox">
                          劉建國等16人｜113/00/00 提案版本
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="tags">
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                    <span class="tag">
                      第Ｏ屆｜劉建國等16人｜113/00/00 提案版本
                      <i class="bi bi-x-lg"></i>
                    </span>
                  </div>
                </div>
              </div>

              <div class="compare-section">
                <div class="title">
                  調整條文範圍
                </div>
                <div class="dropdown-select">
                  <div class="selected-item">
                    113/12/12修正通過(現行)
                    <i class="bi icon bi-chevron-down"></i>
                  </div>
                  <div class="dropdown-menu select-list">
                    <input type="text" placeholder="搜尋" class="form-control filter-input">
                    <div class="scroller">
                      <div class="dropdown-item">
                        <input type="checkbox">
                        第一條
                      </div>
                      <div class="dropdown-item">
                        <input type="checkbox">
                        第二條
                      </div>
                      <div class="dropdown-item">
                        <input type="checkbox">
                        第三條
                      </div>
                    </div>
                  </div>
                </div>

                <div class="tags">
                  <span class="tag">
                    第二條
                    <i class="bi bi-x-lg"></i>
                  </span>
                  <span class="tag">
                    第六條
                    <i class="bi bi-x-lg"></i>
                  </span>
                  <span class="tag">
                    第九十二條
                    <i class="bi bi-x-lg"></i>
                  </span>
                  <a href="#" class="show-all">
                    顯示全部
                  </a>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal">確認</button>
            </div>
          </div>
        </div>
      </div>
    </div>
<?= $this->partial('common/footer') ?>
