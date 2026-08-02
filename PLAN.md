# PLAN.md — Lawtrace（立法足跡）專案導覽

> 本文件供 AI Agent 快速掌握本專案架構與修改方式，避免每次都要重新掃描全部原始碼。
> 若本文件與實際程式碼有出入，一律以程式碼為準，並請更新本文件。

## 專案是什麼

Lawtrace（立法足跡）是 OpenFun Ltd. 開發的「立法院法律沿革查詢」網站。使用者可以查詢：
- 一部法律的歷史版本、每個版本的完整條文
- 版本之間的條文差異（diff）
- 某個法案／委員會會議／三讀版本／行政院部預告版本 背後牽涉哪些議案，並排出「對照表」讓使用者比較不同提案版本的條文差異
- 首頁列出近期三讀、審查中、審查完畢的法案

**沒有資料庫**：所有資料都即時向立法院開放資料 API（LYAPI，`https://v2.ly.govapi.tw`）與
JOIN 平台部預告版 API（PolicyAPI，`https://policy.join.govapi.tw`）查詢，本地只做檔案快取
（`/tmp/lyapicache-*.json`，見 `libraries/LYAPI.php`）。

## 技術棧

- PHP，框架為 **mini-engine**（OpenFun 自製的輕量單檔 MVC framework，`mini-engine.php`）。
  這是開源專案：https://github.com/openfunltd/mini-engine ，repo 內的 `MINI_ENGINE_GUIDE.md`
  有完整的框架使用說明（Controller / View / 路由 / autoload / DB 等規則），也包含一段
  專門介紹本專案（lawtrace）的說明。**修改本專案前建議先讀那份指南**，本文件不重複列出
  框架本身的用法，只記錄 lawtrace 專屬的架構與慣例。
- 前端：伺服器端 render 的 PHP view + jQuery（`static/js/*.js`），無前端框架、無建置流程。
- 唯一 composer 依賴：`lolli42/finediff`（文字 diff 演算法，用於 `views/law/diff.php` /
  `libraries` 中的 diff 呈現）。
- 本地啟動：`php -S 0:8888 route.php`（`route.php` 會把非 `/static` 開頭的請求導到
  `index.php` → `MiniEngine::dispatch()`）。

## 目錄結構

```
lawtrace/
├── index.php / init.inc.php / route.php   # mini-engine 標準入口，見框架文件
├── config.sample.inc.php                  # 環境變數範本，實際 config.inc.php 不進 git
├── controllers/
│   ├── IndexController.php    # 首頁、robots.txt
│   ├── LawController.php      # 核心：/law/* 所有查詢與比較功能（最重要、最大的 controller）
│   ├── SearchController.php   # 搜尋、院會議程頁
│   ├── AboutController.php    # 關於頁（純靜態）
│   └── ErrorController.php    # mini-engine 例外處理入口
├── libraries/                  # 所有商業邏輯都在這裡（無 models/，因為沒有 DB）
├── views/{controller}/{action}.php  # 對應 controller/action
└── static/{css,js,images}
```

## 路由 → 頁面對應

沿用 mini-engine 預設路由（`/{controller}/{action}/{params...}`），本專案只在 `index.php`
額外處理 `/robots.txt`。實際上重要的頁面：

| URL | Controller::Action | 用途 |
|---|---|---|
| `/` | `IndexController::indexAction` | 首頁：近期三讀 / 審查完畢 / 審查中列表 |
| `/law/show/{law_id}` | `LawController::showAction` | 顯示某法律某版本的完整條文 |
| `/law/history/{law_id}` | `LawController::historyAction` | 顯示某法律的立法歷程（時間軸） |
| `/law/single/{law_content_id}` | `LawController::singleAction` | 單一條文跨版本比較 |
| `/law/diff/{law_id}` | `LawController::diffAction` | 前後兩版本條文差異 |
| `/law/compare` | `LawController::compareAction` | 核心功能：依 `?source=` 找出相關議案，產出對照表 |
| `/law/sub_laws/{law_id}` | `LawController::sub_lawsAction` | 該法律的子法列表 |
| `/law/billdata` | `LawController::billdataAction` | AJAX，回傳單一議案的版本資料（JSON） |
| `/search` | `SearchController::indexAction` | 搜尋（若輸入為 billNo 直接轉址到 `/law/compare`） |
| `/search/plenary` | `SearchController::plenaryAction` | 院會議程頁 |
| `/about` | `AboutController::indexAction` | 關於頁 |

## `source` 參數：貫穿全站的核心概念

`/law/history` 與 `/law/compare` 都吃一個 `source` 參數，格式為 `{type}:{...}`，代表「這次
查詢是從哪個入口點找相關議案」。這是理解 `LawController`、`DiffHelper`、`LawHistoryHelper`、
`PolicyHelper` 互動邏輯的關鍵：

| type | 格式範例 | 意義 |
|---|---|---|
| `meet` | `meet:委員會-11-2-23-20:02017` | 從委員會審查會議查該次會議相關議案（第三段是 law_id） |
| `bill` | `bill:203110083270000` | 從單一議案（含其關連議案）查 |
| `version` | `version:01254:2024-12-31` | 從三讀版本查該次三讀的完整歷程與相關議案 |
| `join-policy` | `join-policy:{policy_uid}:{law_id}` | 從行政院／各部會在 JOIN 平台的部預告版查 |
| `custom` | `custom:{law_id}` | `/law/compare` 專用，使用者自訂比較基準與對象 |

`DiffHelper::getBillNosFromSource()` 把 `source` 轉成一組議案編號（billNos），
`DiffHelper::getVersionsFromBillNos()` 再把這些議案編號轉成可比較的「版本」陣列
（每個版本含條文對照表），最後 `DiffHelper::mergeVersionsToTable()` 合併成
`/law/compare` 頁要呈現的對照表結構（`$ret->rule_diffs`，以條號分組、每條下面各版本的內容）。

## `libraries/` 職責一覽

- `LYAPI.php` / `PolicyAPI.php` — 對外 API 的低階 client，統一走 `apiQuery($url, $reason, $cache)`。
  帶檔案快取（存在 `/tmp/`），並記錄慢查詢（`/tmp/lyapi-slow-*`）供除錯。**新增任何查詢時都要
  透過這兩個 class，不要自己開 curl。**
- `LawVersionHelper.php` — 依 `law_id` + `version_id_input`（`latest` 或 `{law_id}:{date}` 或
  `{law_id}:{term}-progress`）算出目前選中的版本、上一個版本、依屆期分組的版本列表。
  `getVersionsWithProgresses()` 額外處理「還沒三讀 / 還在審查中」法案的進度資料（無版本編號）。
- `LawHistoryHelper.php` — `/law/history` 頁時間軸資料的主要組裝邏輯：把 API 回傳的
  `歷程/bill_log` 補上議案詳細資料、委員會會議資料、公報連結、黨籍圖示、附帶決議連結等，
  最後依日期分組、排序成 timeline。**這支最大也最複雜，改動前建議先讀對應的 view
  (`views/law/history.php`, `views/partial/law_history_timeline.php`) 對照理解資料結構。**
- `DiffHelper.php` — 見上方「`source` 參數」說明，是 `/law/compare` 的核心運算。另含
  `ruleNoToNumber()` / `sortRuleNo()` 負責把「第 12 條之 3」這類中文條號轉數字排序。
- `PolicyHelper.php` — 部預告版（JOIN 平台）相關的資料串接，包含把部預告版插入
  `history_groups` 時間軸、以及產生 `/law/compare` 用的對照表格式。
- `LyTcToolkit.php` — 中文數字（含大寫「壹貳參」、全形數字）解析成整數，供條號排序使用。
- `LyDateHelper.php` — 屆期起訖日期表（`$term_dates`），依日期反查屆別。
- `LawChapterHelper.php` — 從「章名」字串取出「篇/章/節/款/目」單位。
- `PartyHelper.php` — 依委員姓名查黨籍、依黨籍回傳對應的 SVG 圖示路徑（`static/images/party/`）。
- `SearchHelper.php` — `/search/plenary` 院會議程頁的資料組裝。
- `IndexHelper.php` — 首頁三個列表（近期三讀 / 審查完畢 / 審查中）的資料組裝，另有抓取
  OpenFun News 公告的 `getOpenfunLog()`（目前似乎未被呼叫，若要用可檢查是否還有頁面引用）。
- `MetadataHelper.php`（類別名稱是 `MetaDataHelper`，注意大小寫）— `/law/history`、
  `/law/compare` 頁上方 metadata 卡片的標題／說明文字，依 `source` 的 type 對應。
- `MiniEngineHelper.php` — 目前只有一個 `uniqid()` 亂數字串產生器。

## Views 慣例

- 沒有使用 mini-engine 的 `yield` layout 機制；每個 view 直接在檔案開頭 / 結尾各自
  `partial('common/header', $this)` / `partial('common/footer')`。
- `views/common/header.php` 需要拿到目前 controller 的 view 資料（傳 `$this` 進去），因為
  header 內要判斷目前頁面高亮選單等。
- `views/partial/` 放跨頁共用的區塊（側欄、歷程選單、歷程時間軸）。

## 開發須知 / 常見陷阱（源自過往修 bug 的經驗）

1. **API 回傳可能是 `null` 或缺欄位**：立法院 API 資料品質不穩定（尤其是舊資料、還在
   審議中的法案），大量程式碼用 `??`、`property_exists()` 防呆。新增邏輯時務必假設任何
   欄位都可能不存在，避免 PHP 8 的 deprecated/warning（過去多次提交都是在修這類問題，
   見 `cc4e786`、`199c433`）。
2. **`version_id_input` 有三種格式**：`'latest'`、`{law_id}:{date}`（對應已公告的三讀版本）、
   `{law_id}:{term}-progress`（對應還沒三讀、只能看審議進度的法案）。任何處理版本的程式碼
   都要考慮這三種情況。
3. **總統府尚未公告的三讀法案**：法律系統查無版本時，`LawController` 會另外呼叫
   `/laws?limit=100` 檢查是否為「三讀已過但總統府尚未公告」的情況（`$is_announced`），
   這是使用者常見的困惑來源，修改三讀相關邏輯時要留意。
4. **中文條號排序**：條號不是單純字串排序，需先用 `LyTcToolkit::parseNumber()` 轉數字再比較
   （見 `DiffHelper::ruleNoToNumber()` / `sortRuleNo()`）。
5. **快取**：`LYAPI::apiQuery()` 預設快取 3600 秒，存在 `/tmp/lyapicache-*.json`。除錯時可在
   URL 加 `?nocache=1` 略過快取；改資料結構後記得本機快取檔可能是舊格式。
6. **Excel 匯出**：`/law/compare` 頁的「下載 Excel」改用 ExcelJS 在前端產生（見
   `static/js/download_xlsx.js`），非後端產生，格式需與畫面上的 diff 顏色一致。

## 重大開發歷程（依 git log 整理）

專案自 2024-11-14 第一個 commit 至今（2026-07）約 500 個 commit。2025-01～03 是密集打地基
的階段，之後轉為每次針對一個 GitHub issue（`#N`）的小範圍迭代。

| 時間 | 里程碑 | 說明 |
|---|---|---|
| 2024-11-14 | 專案初始化 | `Initial commit` + `add init mini-engine`，套用 mini-engine 骨架 |
| 2024-11-19 | 前端雛型 | 加入 jQuery 3.7.1 / Bootstrap 5.3.3，首頁搜尋框、預設 navbar |
| 2024-11-21 | `/search` 初版 | 建立 `SearchController`，串接 `LYAPI.php`，可搜尋法律／條文 |
| 2024-11-22 | `/law/show` 初版 | 法律版本 + 條文顯示的第一版 |
| 2024-11-25 | `/law/history`、`/law/bill` 初版 | 立法歷程頁最早的原型（`/law/bill` 之後於 2024-12-03 移除） |
| 2024-11-26 | `/lawarticle/show` 初版 | 單一條文頁原型（後續演變為現在的 `/law/single`） |
| 2024-12-02 | `/lawdiff/show` 初版 | 導入 `lolli42/finediff`，第一版「條文差異比對」頁（後續演變為 `/law/diff`） |
| 2024-12-18~19 | 與 `lawtrace-frontend` 整合 | 併入外部設計稿／前端切版成果，取代早期陽春版面 |
| 2025-01 | 歷程頁重構 | 併入前端切版到 `/law/history`，加入 `PartyHelper` 與政黨圖示、版本選擇 UI |
| 2025-02 | 首頁與 `/law/compare` 誕生 | `IndexHelper` 生出首頁三份清單（近期三讀／審查報告／審查中會議）；`fb0a4b3` 起從零建立 `/law/compare`，同時新增 `DiffHelper`、`LyTcToolkit`（中文數字解析、條號排序） |
| 2025-02-27 | issue #3：`/law/history` 支援多時間軸 | 抽出 `views/partial/law_history_timeline.php`，一個法律可以有多條並行歷程 |
| 2025-03 | 對照表體驗打磨 + SEO | `/law/compare` 加上橫向捲動、`/search/plenary`（院會議程）頁上線；補上 meta description、OG image 等 SEO 資訊 |
| 2025-08 | issue #5 | 顯示「總統府尚未公告」版本的提示文字（後續在 2026-01 issue #48 再強化） |
| 2025-08～09 | issue #12：`/law/sub_law/{law_id}` | 新增子法列表頁 |
| 2025-10 | issue #25、#28 | #25：從公報字串解析卷/期/冊/頁碼；#28：`/law/compare` 加入 Excel 下載（當時用 sheetjs，2026 年後改為 ExcelJS，見下方常見陷阱第 6 點） |
| 2025-11 | issue #35 | `/law/show` 加入法規沿革（`law_reason_source_version`）來源版本資訊 |
| 2025-12 | issue #36：部預告版整合 | 新增 `PolicyAPI` / `PolicyHelper`，把行政院 JOIN 平台「眾開講」部預告版串進 `/law/history` 時間軸，新增 `source` type：`join-policy` |
| 2026-01 | issue #42：比較對象動線大改版 | 目前 `/law/compare` 的「新增比較對象」互動、`source_type` 為 `custom` 的邏輯、`MetadataHelper` 卡片文案，多數於此次迭代成形（33 個 commit，最大單一 issue） |
| 2026-01 | issue #47、#48、#49 | 修正公報發言紀錄缺資料、總統府尚未公布三讀的顯示邏輯、「逕付二讀」狀態判斷 |
| 2026-02 起 | 維運期 | 主要是效能（cache、slow log）、PHP 8 deprecated/warning 修正、爬蟲規則（`robots.txt`）等小幅調整，無新增大功能 |

**如何自行深入某段歷史**：`git log --grep="#42"` 之類可抓出單一 issue 的所有 commit；
`git log --reverse --format='%ad %h %s' --date=short` 可看到完整時間序。

## 參考資源

- mini-engine 框架（開源）：https://github.com/openfunltd/mini-engine
  （repo 內 `MINI_ENGINE_GUIDE.md` 含 lawtrace 專案專屬的一段說明）
- LYAPI 文件：https://v2.ly.govapi.tw/
- SBOM：`SBOM.spdx`
- README：`README.md`
