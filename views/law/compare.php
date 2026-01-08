<?php
if (is_null($this->error)) {
    $version_count = count($this->diff->choosed_version_ids);
    $versions = [];
    foreach ($this->diff->choosed_version_ids as $version_id) {
        $version = $this->diff->versions->{$version_id};
        $versions[] = $version->title;
    }
}
if ($this->source_type == 'bill') {
    if ($this->bill->提案來源 == '審查報告') {
        $this->title = "{$this->law->名稱} | 審查報告";
        $this->description = sprintf("審查完成「%s」，審查委員會：%s\n"
            . "會議日期：%s\n"
            . "相關版本：%s",
            $this->law->名稱,
            str_replace('本院', '', $this->bill->{'提案單位/提案委員'}),
            LawVersionHelper::getMinguoDate($this->bill->議案流程[0]->日期[0] ?? ''),
            implode('、', $versions)
        );
    } else {
        $this->title = "{$this->law->名稱} | {$this->bill->{'提案單位/提案委員'}}";
        $this->description = $this->bill->案由;
    }
} elseif ($this->source_type == 'meet') {
    $this->title = "{$this->law->名稱} | 審查會議";
    $this->description = sprintf("%s\n"
        . "會議日期：%s\n"
        . "召委：%s\n"
        . "審查法案：%s\n"
        . "相關版本：%s",
        $this->meet->會議標題,
        LawVersionHelper::getMinguoDate($this->meet->日期[0]),
        $this->meet->會議資料[0]->委員會召集委員 ?? '',
        $this->law->名稱,
        implode('、', $versions)
    );
} elseif ($this->source_type == 'version') {
    $this->title = "{$this->law->名稱} | 三讀版本";
    $version_date = substr($this->version_id_input, strlen("{$this->law_id}:"));
    if (is_null($this->error)) {
        $this->description = sprintf("三讀日期：%s\n"
            . "相關版本：%s",
            LawVersionHelper::getMinguoDate($version_date),
            implode('、', $versions)
        );
    }
}
$this->body_class = 'law-compare-page';
$this->tab = 'compare';
?>
<?= $this->partial('common/header', $this) ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsdiff/7.0.0/diff.min.js" integrity="sha512-immo//J6lKoR+nRIFDPxoxfL2nd/0N3w8l4LwH4HSSVovtUjab5kbh4AhixLH5z9mIv37llY9Q2i8AfEDXyYjw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <div class="main">
      <?= $this->partial('law/law_hero', $this) ?>
      <?php if (isset($this->error)) { ?>
      <div class="main-content">
        <section class="law-details">
          <div class="container">
            <div class="law-compare-wrapper">
              <div class="alert alert-primary" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                  立法院第 8 屆以前(~2016-01-31)的議案檔案，並非完整的數位化，僅為掃描成圖檔後存成 PDF 的檔案。
                  故無法在本頁利用對照表進行條文比較，還請見諒。
              </div>
            </div>
          </div>
        </section>
      </div>
      <?php exit; ?>
      <?php } ?>
      <div class="main-content">
        <section class="law-details">
          <div class="container">
            <div class="law-compare-wrapper">
              <div class="compare-range">
                <div class="range-setting">
                  <button class="btn btn-primary set-compare-target">
                    設定比較對象
                    <i class="bi bi-pencil-fill ms-1"></i>
                  </button>
                  <button id="download-xlsx" class="btn btn-outline-primary ms-1">
                    匯出 excel
                    <i class="bi bi-download ms-1"></i>
                  </button>
              </div>
                <div class="range-info">
              <!-- TODO: 待實作
                  <div>
                      比較版本：113/00/00 修正版本 － VS － 其他 6 個版本<?php // TODO ?>
                  </div>
                  <div>
                      條文範圍：第 2, 6, 92 條<?php // TODO ?>
                  </div>
              -->
              </div>
                <div class="options">
                  <div class="form-check form-switch expand-law-help">
                    <input class="form-check-input" type="checkbox" role="switch" id="expandLawHelp">
                    <label class="form-check-label" for="expandLawHelp">
                      展開所有立法說明
                    </label>
                  </div>
                  <div class="form-check form-switch show-category">
                    <input class="form-check-input" type="checkbox" role="switch" id="showCategory" checked>
                    <label class="form-check-label" for="showCategory">
                      顯示條文目錄
                    </label>
                  </div>
                  <div class="form-check form-switch show-category">
                    <input class="form-check-input" type="checkbox" role="switch" id="splitContent" checked>
                    <label class="form-check-label" for="splitContent" title="將條文分成一句一句顯示，有可能會分句失敗">
                      分句顯示(BETA)
                    </label>
                  </div>
                </div>
              </div>
              <div class="diff-info rwd-full">
                <label>
                  <input type="radio" class="form-check-input" name="diff-type" value="none" checked>
                  顯示原文
                </label>
                <label>
                  <input type="radio" class="form-check-input" name="diff-type" value="only_add">
                  顯示<span class="add">新增</span>
                </label>
                <label>
                  <input type="radio" class="form-check-input" name="diff-type" value="update">
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
                        <a href="#section-<?= $idx ?>">
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
                  <?php if ($version_count != 1) { ?>
                    <div class="original compare-head">
                      比較基準
                    </div>
                    <div class="compare-head">
                      比較對象
                      <span class="compare-scroll-btns">
                        <a role="button" class="link-primary cursor-pointer" onclick="scroll_compare_horizontal('left');">
                          <i class="bi bi-arrow-left-circle"></i>
                        </a>
                        <a role="button" class="link-primary cursor-pointer" onclick="scroll_compare_horizontal('right');">
                          <i class="bi bi-arrow-right-circle"></i>
                        </a>
                      </span>
                    </div>
                  <?php } ?>
                  <!-- 如果大於2時，要以空白div補足數量 -->
                  <?php for ($i = 0; $i < $version_count - 2; $i ++) { ?>
                  <div class="compare-head"></div><!-- 用空白div的數量補足column數量 -->
                  <?php } ?>

                  <!-- content-section-head -->
                  <?php foreach ($this->diff->choosed_version_ids as $version_id) { ?>
                  <?php $version = $this->diff->versions->{$version_id} ?>
                  <div class="law-diff-head <?= $version->first_version ? 'original' : '' ?>">
                    <div class="title" title="<?= $version->id ?>">
                        <?php if ($version->party_img ?? false) { ?>
                          <img width="16" height="16" src="<?= $this->escape($version->party_img) ?>">
                        <?php } ?>
                        <?= $this->escape($version->title) ?>
                        <small>
                            <?= $this->escape($version->subtitle) ?>
                        </small>
                    </div>
                    <div class="action">
                      <div class="dropstart">
                        <span data-bs-toggle="dropdown" data-bs-boundary="scrollParent">
                          <i class="bi bi-three-dots-vertical"></i>
                        </span>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                          <li>
                            <a class="dropdown-item" href="<?= $this->escape($version->原始資料) ?>" target="_blank">
                              <i class="bi bi-box-arrow-up-right"></i>
                              查看原始資料
                            </a>
                          </li>
                          <?php if ($version->議案編號 ?? false) { ?>
                          <li>
                            <a class="dropdown-item" href="/law/compare?source=bill:<?= $version->議案編號 ?>" target="_blank">
                              查看此版本資訊
                            </a>
                          </li>
                          <?php } ?>
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
                  <div class="original law-diff-title" id="section-<?= $idx ?>">
                      <?= $this->escape($rule_diff->條文) ?>
                  </div>
                  <?php for ($i = 0; $i < $version_count - 1; $i ++) { ?>
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <?php } ?>

                  <!-- content-section 1 -->
                  <?php foreach ($this->diff->choosed_version_ids as $version_id) { ?>
                    <?php $version = $this->diff->versions->{$version_id} ?>
                    <div
                      class="<?= $this->if($version->first_version, 'original', '') ?> law-diff-content law-diff-content-origin" 
                      data-version="<?= $this->escape($version->id) ?>"
                      data-rule-no="<?= $this->escape($idx) ?>"
                    >
                      <span class="law-diff-content-text"><?= nl2br($rule_diff->versions->{$version->id}->內容 ?? '') ?></span>

                      <?php if ($rule_diff->versions->{$version->id}->說明 ?? false) { ?>
                        <div class="card-help">
                          <div class="help-title">
                            立法說明
                            <i class="bi bi-chevron-down icon"></i>
                          </div>
                          <div class="help-body"><?= nl2br(htmlspecialchars($rule_diff->versions->{$version->id}->說明)) ?></div>
                        </div>
                      <?php } ?>
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
                <?= $this->escape($this->law->名稱) ?>
                <br>
                調整比較對象
                <!-- 自訂條文時需更改為：自訂比較對象 -->
              </h6>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="compare-section">
                <div class="title">
                  <div class="d-flex">
                    <div class="flex-fill">
                      顯示/隱藏
                    </div>
                    <div>
                      設為比較基準
                    </div>
                  </div>
              </div>
<script id="tmpl-version-list" type="text/template">
<div class="form-check form-switch flex-fill">
    <input class="form-check-input" type="checkbox" name="versions[]" checked>
    <label class="form-check-label" for="ver_1">
    劉建國等16人｜113/00/00 第幾院期提案版本
    </label>
    </div>
    <div>
    <input class="form-check-input" type="radio" name="radio1" checked>
    </div>
</script>
                <div class="version-list">
                </div>

                <!-- 非自訂條文時，modal下方選單為以下內容 begin -->
                <div class="my-3">
                  <button class="btn btn-outline-primary" id="btn-custom-compare">
                    自訂比較對象
                  </button>
              </div>
              <?php if ($this->source_type == 'custom') { ?>
                <!-- 非自訂條文時，modal下方選單為以下內容 end -->

                <!-- 自訂條文時，modal下方選單為以下內容 begin -->
                <div class="d-flex gap-3 align-items-center my-3">
                  <div class="flex-fill dropdown-select">
                    <div class="selected-item" id="version-select-selected">
                        <span class="text">選擇屆期或是修正版本</span>
                      <i class="bi icon bi-chevron-down"></i>
                  </div>
                    <div class="dropdown-menu select-list">
                      <div class="scroller" id="version-select-list">
                      </div>
                    </div>
                  </div>
                  <div>
                    <button class="btn btn-outline-primary">
                      <i class="bi icon bi-plus"></i>
                      新增比較對象
                    </button>
                  </div>
                </div>
                <!-- 自訂條文時，modal下方選單為以下內容 end -->

                <!-- 新增比較對象按鈕點選後，modal內為以下內容 begin -->
                <div class="dropdown-select">
                  <div class="selected-item">
                    請選擇條文版本
                    <i class="bi icon bi-chevron-down"></i>
                  </div>
                  <div class="dropdown-menu select-list">
                    <div class="scroller" id="version-choose-list">
                      <span class="dropdown-item">
                        113/12/12 修正通過修正通過修正通過修正通過修正通過修正通過修正通過（現行）
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
                <!-- 新增比較對象按鈕點選後，modal內為以下內容 end -->
              </div>
          </div>
          <?php } ?>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="btn-update-compare">確認</button>
              <!-- 新增比較對象按鈕點選後，modal內為以下按鈕 end -->
              <!--<button type="button" class="btn btn-primary" data-bs-dismiss="modal">新增比較對象</button>-->
              <!-- 新增比較對象按鈕點選後，modal內為以下按鈕 end -->
            </div>
          </div>
        </div>
      </div>
  </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" integrity="sha512-r22gChDnGvBylk90+2e/ycr3RVrDi8DIOkIGNhJlKfuyQM4tIRAI062MaV8sfjQKYVGjOBaZBOA87z+IhZE9DA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
diff_data = <?= json_encode([
    'diff' => $this->diff,
    'all_version_ids' => $this->all_version_ids,
    'choosed_version_ids' => $this->choosed_version_ids,
    'source' => $this->source,
    'law_id' => $this->law_id,
]) ?>;
ly_api_base = <?= json_encode("https://" . getenv('LYAPI_HOST')) ?>;
</script>
<script src="/static/js/diff.js"></script>
<script src="/static/js/scroll.js"></script>
<script src="/static/js/download_xlsx.js"></script>
<?= $this->partial('common/footer') ?>
