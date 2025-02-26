<?php
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

$versions_data = LawVersionHelper::getVersionsWithProgresses($law_id, $version_id_input);
$versions = $versions_data->versions;
$versions_in_terms = $versions_data->versions_in_terms;
$version_selected = $versions_data->version_selected;
$version_id_selected = $versions_data->version_id_selected;
$term_selected = $versions_data->term_selected;
if (is_null($version_selected)) {
    header('HTTP/1.1 404 No Found');
    echo "<h1>404 No Found</h1>";
    echo "<p>No version data with version_id {$version_id_input}</p>";
    exit;
}

$history_groups = $version_selected->歷程 ?? [];
$history_groups = LawHistoryHelper::updateDetails($history_groups, $term_selected);
$is_progress_history = (strpos($version_id_selected, 'progress') !== false);

$aliases = $law->其他名稱 ?? [];
$vernaculars = $law->別名 ?? [];
$show_endpoint = "/law/show/{$law_id}";
$diff_endpoint = "/law/diff/{$law_id}";
if ($version_id_input != 'latest' and !$is_progress_history) {
    $show_endpoint = $show_endpoint . "?version={$version_id_input}";
    $diff_endpoint = $diff_endpoint . "?version={$version_id_input}";
}
?>
<?php $law_name = $this->escape($law->名稱 ?? ''); ?>
<?= $this->partial('common/header', ['title' => "{$law_name} - 經歷過程"]) ?>
<div class="main">
  <section class="page-hero law-details-info">
    <div class="container">
      <nav class="breadcrumb-wrapper">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="/">
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
                            <a href="/law/history/<?= $this->escape($law_id) ?>?version=<?= $this->escape($version->版本編號) ?>">
                              <?php if (property_exists($version, '動作')) { ?>
                                <?= $this->escape("{$version->民國日期_format2} {$version->動作}") ?>
                              <?php } elseif ($is_current_term) { ?>
                                尚未議決議案
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
            <ul class="nav nav-tabs">
              <?php if (!$is_progress_history) { ?>
                <li class="nav-item">
                  <a class="nav-link" href="<?= $this->escape($diff_endpoint) ?>">異動條文</a>
                </li>
              <?php } ?>
              <li class="nav-item">
                <a class="nav-link active" href="#">經歷過程</a>
              </li>
            </ul>
            <?php if ($is_progress_history) { ?>
              <?= $this->partial('partial/law_history_menu', ['history_groups' => $history_groups]) ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
