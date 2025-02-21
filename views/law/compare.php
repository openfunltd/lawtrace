<?php
$source_input = $this->source_input;
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
                    比較版本：113/00/00 修正版本 － VS － 其他 6 個版本
                  </div>
                  <div>
                    條文範圍：第 2, 6, 92 條
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
                      <div class="menu-head">
                        <a href="#section-1">
                          第一條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第二條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第三條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                      <div class="menu-head">
                        <a href="#section-1">
                          第四條
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="law-diff-row-wrapper">
                <!--
                  ** 這邊請後端將--col-count帶入column數量 **
                  ** 如果沒有選擇比較對象，要填2（最小就是2） **
                -->
                <div class="law-diff-row law-diff-header-row" style="--col-count: 5;">
                  <!-- 比較基準 & 比較對象 -->
                  <div class="original compare-head">
                    比較基準
                  </div>
                  <div class="compare-head">
                    比較對象
                  </div>
                  <!-- 如果大於2時，要以空白div補足數量 -->
                  <div class="compare-head"></div><!-- 用空白div的數量補足column數量 -->
                  <div class="compare-head"></div><!-- 用空白div的數量補足column數量 -->
                  <div class="compare-head"></div><!-- 用空白div的數量補足column數量 -->

                  <!-- content-section-head -->
                  <div class="original law-diff-head">
                    <div class="title">
                      113/12/12修正通過(現行)
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
                  <div class="law-diff-head">
                    <div class="title">
                      <img src="images/party/kmt.svg">
                      陳亭妃等16人
                      <small>
                        113/00/00 提案版本
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
                          <li>
                            <a class="dropdown-item" href="law_details_single.html">
                              <i class="bi bi-trash"></i>
                              刪除此比較對象
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-head">
                    <div class="title">
                      <img src="images/party/dpp.svg">
                      李大偉等16人
                      <small>
                        113/00/00 提案版本
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
                          <li>
                            <a class="dropdown-item" href="law_details_single.html">
                              <i class="bi bi-trash"></i>
                              刪除此比較對象
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-head">
                    <div class="title">
                      <img src="images/party/tsu.svg">
                      王小明等16人
                      <small>
                        113/00/00 提案版本
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
                          <li>
                            <a class="dropdown-item" href="law_details_single.html">
                              <i class="bi bi-trash"></i>
                              刪除此比較對象
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-head">
                    <div class="title">
                      <img src="images/party/tpp.svg">
                      張婷婷等16人
                      <small>
                        113/00/00 提案版本
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
                          <li>
                            <a class="dropdown-item" href="law_details_single.html">
                              <i class="bi bi-trash"></i>
                              刪除此比較對象
                            </a>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>

                <!--
                  ** 這邊請後端將--col-count帶入column數量 **
                  ** 如果沒有選擇比較對象，要填2（最小就是2） **
                -->
                <div class="law-diff-row" style="--col-count: 5;">
                  <!-- content-section-title -->
                  <div class="original law-diff-title">
                    第二條
                  </div>
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->

                  <!-- content-section 1 -->
                  <div class="original law-diff-content">
                    本條例所稱兒童或少年性剝削，指下列行為之一者：<br>
                    一、使兒童或少年為有對價之性交或猥褻行為。<br>
                    二、利用兒童或少年為性交或猥褻之行為，以供人觀覽。
                  </div>
                  <div class="law-diff-content no-modification">
                    本條例所稱兒童或少年性剝削，指下列行為之一者：<br>
                    一、使兒童或少年為有對價之性交或猥褻行為。<br>
                    二、利用兒童或少年為性交或猥褻之行為，以供人觀覽。
                  </div>
                  <div class="law-diff-content">
                    本條例所稱兒童或少年性剝削，指下列行為之一者：<br>
                    一、<span class="add">這是新增的字</span>使兒童或少年為有對價之性交或猥褻行為。<br>
                    二、利用兒童或少年為性交或猥褻之行為<span class="remove">，以供人觀覽。</span><br>
                  </div>
                  <div class="law-diff-content">
                    本條例所稱兒童或少年性剝削，指下列行為之一者：<br>
                    一、<span class="add">這是新增的字</span>使兒童或少年為有對價之性交或猥褻行為。<br>
                    二、利用兒童或少年為性交或猥褻之行為<span class="remove">，以供人觀覽。</span><br>
                  </div>
                  <div class="law-diff-content no-modification">
                    本條例所稱兒童或少年性剝削，指下列行為之一者：<br>
                    一、使兒童或少年為有對價之性交或猥褻行為。<br>
                    二、利用兒童或少年為性交或猥褻之行為，以供人觀覽。
                  </div>

                  <!-- content-section 2 -->
                  <div class="original law-diff-content">
                    三、拍攝、製造、散布、播送、交付、公然陳列或販賣兒童或少年之性影像、與性相關而客觀上足以引起、刺激或滿足性慾而無藝術性、醫學性或教育性價值而令一般人感覺不堪呈現於眾或不能忍受而排拒之圖畫、語音或其他物品。
                  </div>
                  <div class="law-diff-content no-modification">
                    三、拍攝、製造、散布、播送、交付、公然陳列或販賣兒童或少年之性影像、與性相關而客觀上足以引起、刺激或滿足性慾而無藝術性、醫學性或教育性價值而令一般人感覺不堪呈現於眾或不能忍受而排拒之圖畫、語音或其他物品。
                  </div>
                  <div class="law-diff-content no-modification">
                    三、拍攝、製造、散布、播送、交付、公然陳列或販賣兒童或少年之性影像、與性相關而客觀上足以引起、刺激或滿足性慾而無藝術性、醫學性或教育性價值而令一般人感覺不堪呈現於眾或不能忍受而排拒之圖畫、語音或其他物品。
                  </div>
                  <div class="law-diff-content no-modification">
                    三、拍攝、製造、散布、播送、交付、公然陳列或販賣兒童或少年之性影像、與性相關而客觀上足以引起、刺激或滿足性慾而無藝術性、醫學性或教育性價值而令一般人感覺不堪呈現於眾或不能忍受而排拒之圖畫、語音或其他物品。
                  </div>
                  <div class="law-diff-content no-modification">
                    三、拍攝、製造、散布、播送、交付、公然陳列或販賣兒童或少年之性影像、與性相關而客觀上足以引起、刺激或滿足性慾而無藝術性、醫學性或教育性價值而令一般人感覺不堪呈現於眾或不能忍受而排拒之圖畫、語音或其他物品。
                  </div>

                  <!-- content-section-title -->
                  <div class="original law-diff-title">
                    第三條
                  </div>
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->
                  <div class="law-diff-title"></div><!-- 因為是grid排版這個空白的div不可刪 -->

                  <!-- content-section 3 -->
                  <div class="original law-diff-content">
                    四、使兒童或少年坐檯陪酒或涉及色情之伴遊、伴唱、伴舞或其他類似行為。<br>
                    本條例所稱被害人，指遭受性剝削或疑似遭受性剝削之兒童或少年。<br>
                    本條例所稱行為人，指行為滿足第一項各款行為者。<br>
                    <div class="card-help">
                      <div class="help-title">
                        立法說明
                        <i class="bi bi-chevron-down icon"></i>
                      </div>
                      <div class="help-body">
                        一、考量本條例第三十六條、第三十九條、第四十四條皆已將重製、持有或支付對價觀覽列為處罰樣態，爰於第一項第三款增列行為樣態，以擴大保護對象範疇。<br>
                        二、為防制真實存在之兒童或少年遭受任何形式之性剝削，保護其身心健全發展，並利具體認定，有關第一項第三款所稱與性相關而客觀上足以引起性慾或羞恥之圖畫，參酌日本「兒童買春、兒童色情關係行為等規制及處罰並兒童保護等相關法律」，係以生成式人工智慧製造、擬真繪製或以真實存在之兒童或少年為創作背景之色情圖畫為限。至其餘兒童或少年與性相關而客觀上足以引起性慾或羞恥之圖畫除有刑法第二百三十五條規定之適用外，應依兒童及少年福利與權益保障法第四十六條規定，採取明確可行之防護措施，限制接取、瀏覽，以求周妥。
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-content no-modification">
                    四、使兒童或少年坐檯陪酒或涉及色情之伴遊、伴唱、伴舞或其他類似行為。<br>
                    本條例所稱被害人，指遭受性剝削或疑似遭受性剝削之兒童或少年。<br>
                    本條例所稱行為人，指行為滿足第一項各款行為者。<br>
                    <div class="card-help">
                      <div class="help-title">
                        立法說明
                        <i class="bi bi-chevron-down icon"></i>
                      </div>
                      <div class="help-body">
                        一、考量本條例第三十六條、第三十九條、第四十四條皆已將重製、持有或支付對價觀覽列為處罰樣態，爰於第一項第三款增列行為樣態，以擴大保護對象範疇。<br>
                        二、為防制真實存在之兒童或少年遭受任何形式之性剝削，保護其身心健全發展，並利具體認定，有關第一項第三款所稱與性相關而客觀上足以引起性慾或羞恥之圖畫，參酌日本「兒童買春、兒童色情關係行為等規制及處罰並兒童保護等相關法律」，係以生成式人工智慧製造、擬真繪製或以真實存在之兒童或少年為創作背景之色情圖畫為限。至其餘兒童或少年與性相關而客觀上足以引起性慾或羞恥之圖畫除有刑法第二百三十五條規定之適用外，應依兒童及少年福利與權益保障法第四十六條規定，採取明確可行之防護措施，限制接取、瀏覽，以求周妥。
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-content no-modification">
                    四、使兒童或少年坐檯陪酒或涉及色情之伴遊、伴唱、伴舞或其他類似行為。<br>
                    本條例所稱被害人，指遭受性剝削或疑似遭受性剝削之兒童或少年。<br>
                    本條例所稱行為人，指行為滿足第一項各款行為者。<br>
                    <div class="card-help">
                      <div class="help-title">
                        立法說明
                        <i class="bi bi-chevron-down icon"></i>
                      </div>
                      <div class="help-body">
                        一、考量本條例第三十六條、第三十九條、第四十四條皆已將重製、持有或支付對價觀覽列為處罰樣態，爰於第一項第三款增列行為樣態，以擴大保護對象範疇。<br>
                        二、為防制真實存在之兒童或少年遭受任何形式之性剝削，保護其身心健全發展，並利具體認定，有關第一項第三款所稱與性相關而客觀上足以引起性慾或羞恥之圖畫，參酌日本「兒童買春、兒童色情關係行為等規制及處罰並兒童保護等相關法律」，係以生成式人工智慧製造、擬真繪製或以真實存在之兒童或少年為創作背景之色情圖畫為限。至其餘兒童或少年與性相關而客觀上足以引起性慾或羞恥之圖畫除有刑法第二百三十五條規定之適用外，應依兒童及少年福利與權益保障法第四十六條規定，採取明確可行之防護措施，限制接取、瀏覽，以求周妥。
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-content no-modification">
                    四、使兒童或少年坐檯陪酒或涉及色情之伴遊、伴唱、伴舞或其他類似行為。<br>
                    本條例所稱被害人，指遭受性剝削或疑似遭受性剝削之兒童或少年。<br>
                    本條例所稱行為人，指行為滿足第一項各款行為者。<br>
                    <div class="card-help">
                      <div class="help-title">
                        立法說明
                        <i class="bi bi-chevron-down icon"></i>
                      </div>
                      <div class="help-body">
                        一、考量本條例第三十六條、第三十九條、第四十四條皆已將重製、持有或支付對價觀覽列為處罰樣態，爰於第一項第三款增列行為樣態，以擴大保護對象範疇。<br>
                        二、為防制真實存在之兒童或少年遭受任何形式之性剝削，保護其身心健全發展，並利具體認定，有關第一項第三款所稱與性相關而客觀上足以引起性慾或羞恥之圖畫，參酌日本「兒童買春、兒童色情關係行為等規制及處罰並兒童保護等相關法律」，係以生成式人工智慧製造、擬真繪製或以真實存在之兒童或少年為創作背景之色情圖畫為限。至其餘兒童或少年與性相關而客觀上足以引起性慾或羞恥之圖畫除有刑法第二百三十五條規定之適用外，應依兒童及少年福利與權益保障法第四十六條規定，採取明確可行之防護措施，限制接取、瀏覽，以求周妥。
                      </div>
                    </div>
                  </div>
                  <div class="law-diff-content no-modification">
                    四、使兒童或少年坐檯陪酒或涉及色情之伴遊、伴唱、伴舞或其他類似行為。<br>
                    本條例所稱被害人，指遭受性剝削或疑似遭受性剝削之兒童或少年。<br>
                    本條例所稱行為人，指行為滿足第一項各款行為者。<br>
                    <div class="card-help">
                      <div class="help-title">
                        立法說明
                        <i class="bi bi-chevron-down icon"></i>
                      </div>
                      <div class="help-body">
                        一、考量本條例第三十六條、第三十九條、第四十四條皆已將重製、持有或支付對價觀覽列為處罰樣態，爰於第一項第三款增列行為樣態，以擴大保護對象範疇。<br>
                        二、為防制真實存在之兒童或少年遭受任何形式之性剝削，保護其身心健全發展，並利具體認定，有關第一項第三款所稱與性相關而客觀上足以引起性慾或羞恥之圖畫，參酌日本「兒童買春、兒童色情關係行為等規制及處罰並兒童保護等相關法律」，係以生成式人工智慧製造、擬真繪製或以真實存在之兒童或少年為創作背景之色情圖畫為限。至其餘兒童或少年與性相關而客觀上足以引起性慾或羞恥之圖畫除有刑法第二百三十五條規定之適用外，應依兒童及少年福利與權益保障法第四十六條規定，採取明確可行之防護措施，限制接取、瀏覽，以求周妥。
                      </div>
                    </div>
                  </div>

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
