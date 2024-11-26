<?php
$q = $this->q;

$t1 = hrtime(true);
$res = LYAPI::apiQuery("/laws?q=\"{$q}\"&類別=母法&limit=100", "查詢 laws 關鍵字：{$q}");
$laws = $res->laws;

$laws = array_filter($laws, function($law) {
    return isset($law->最新版本);
});

//查詢 + filter 後如果沒有 law 則改搜尋 law_contents
$no_result_from_law_name = empty($laws);
if ($no_result_from_law_name) {
    $res = LYAPI::apiQuery(
        "/law_contents?q=\"{$q}\"&agg=法律編號",
        "查詢 law_contents 關鍵字: {$q}"
    );
    $law_content_cnt = $res->total ?? 0;
    if ($res->total > 0) {
        $law_buckets = $res->aggs[0]->buckets;
        $law_buckets = array_filter($law_buckets, function($bucket) {
            $law_id = $bucket->法律編號 ?? '';
            return mb_strlen($law_id) == 5;
        });
        $law_buckets = array_map(function ($bucket) {
            return "法律編號={$bucket->法律編號}";
        }, $law_buckets);
        $query_laws = implode('&', $law_buckets);
        $res = LYAPI::apiQuery("/laws?{$query_laws}", "直接透過 law_ids 查詢指定的 law 資料");
        $laws = $res->laws;

        $laws = array_filter($laws, function($law) {
            return isset($law->最新版本);
        });
    }
}

foreach ($laws as $law) {
    $law_content_id =  "{$law->法律編號}:{$law->最新版本->版本編號}";
    $res = LYAPI::apiQuery(
        "/law_contents?q=\"{$q}\"&版本編號={$law_content_id}&limit=30",
        "查詢 {$law->名稱}({$law->法律編號}) 的法條 關鍵字：{$q}"
    );
    $law_contents = [];
    foreach ($res->lawcontents as $law_content) {
        $chapter_name = $law_content->章名 ?? '';
        $content_idx = $law_content->條號 ?? '';
        $content_highlights = $law_content->{'內容:highlight'} ?? [];
        if ($chapter_name != '' or $content_idx == '法律名稱' or empty($content_highlights)) {
            continue;
        }
        $law_contents[] = $law_content;
        if (count($law_contents) == 5) {
            break;
        }
    }
    $law->law_contents = $law_contents;
}

if ($no_result_from_law_name) {
    $laws = array_filter($laws, function($law) {
        return !empty($law->law_contents);
    });
}

$t2 = hrtime(true);
$elapsed_time = number_format(($t2 - $t1) / 1e+9, 2);
?>
<?= $this->partial('common/header', ['title' => 'Lawtrace 搜尋']) ?>
<style>
  em {
    font-style: normal;
    color: red;
  }
</style>
<div class="container bg-light bg-gradient my-5 rounded-3">
  <div class="row p-5">
    <div class="p-4">
      <p class="display-6">LawTrace 進階搜尋</p>
      <form action="/search" method="get">
        <div class="input-group">
          <span class="input-group-text material-symbols-outlined">search</span>
          <input type="text" class="form-control" placeholder="請輸入關鍵字" name="q" required>
        </div>
      </form>
      <div class="mt-2 fs-4">
        <span class="badge rounded-pill text-bg-primary fw-normal">關鍵字：<?= $this->escape($q) ?></span>
      </div>
      <div class="mt-2 fs-6 text-end">
        搜尋時間花費 <?= $this->escape($elapsed_time) ?> 秒
      </div>
    </div>
  </div>
</div>
<?php foreach($laws as $law) { ?>
  <div class="container my-3">
    <div class="row border px-5 py-0 rounded-top-2">
      <div class="col-10 m-0">
        <h2 class="h4 mt-4 mb-0">
          <?php
          $law_name_highlights = $law->{'名稱:highlight'} ?? [];
          if (empty($law_name_highlights)) {
              echo $this->escape($law->名稱);
          } else {
              echo strip_tags($law->{'名稱:highlight'}[0], '<em>');
          }
          ?>
        </h2>
        <?php
        $aliases = $law->其他名稱 ?? [];
        $alias_highlights = $law->{'其他名稱:highlight'} ?? [];
        ?>
        <?php if (!empty($aliases)) { ?>
          <p class="mt-3 mb-0">
            別名：
            <?php
            if (empty($alias_highlights)) {
                echo $this->escape(implode('、', $aliases));
            } else {
                echo strip_tags(implode('、', $alias_highlights), '<em>');
            }
            ?>
          </p>
        <?php } ?>
        <p class="mt-1 mb-0"><?= $this->escape($law->最新版本->版本編號 ?? '') ?><p>
      </div>
      <div class="col-2 d-flex justify-content-center align-items-center">
        <a href="/law/show/<?= $this->escape($law->法律編號) ?>">
          <span class="material-symbols-outlined display-4">arrow_forward</span>
        </a>
      </div>
    </div>
    <?php $law_contents = $law->law_contents; ?>
    <?php if (!empty($law_contents)) { ?>
      <div class="row border border-top-0 px-5 bg-light">
        <div class="col">
          <p class="h5 m-1 py-1">法條內容結果</p>
        </div>
      </div>
      <div class="row border border-top-0 px-5 rounded-bottom-2">
        <div class="col">
          <table class="table table-sm mt-1 ms-3">
            <tbody>
              <?php foreach ($law_contents as $law_content) { ?>
                <tr>
                  <td style="width: 14%;"><?= $this->escape($law_content->條號?? '') ?></td>
                  <td><?= nl2br(strip_tags($law_content->{'內容:highlight'}[0], '<em>')) ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>
  </div>
<?php } ?>
<?= $this->partial('common/footer') ?>
