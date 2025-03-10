<?php
$q = $this->q;
$q_url_encoded = urlencode($q);

$t1 = hrtime(true);
$res = LYAPI::apiQuery("/laws?q=\"{$q_url_encoded}\"&類別=母法&limit=100", "查詢 laws 關鍵字：{$q}");
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

$matched_law_article_cnt = 0;
foreach ($laws as $law) {
    $law_content_id =  "{$law->法律編號}:{$law->最新版本->版本編號}";
    $res = LYAPI::apiQuery(
        "/law_contents?q=\"{$q}\"&版本編號={$law_content_id}&limit=1000",
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
        if (!empty($content_highlights)) {
            $matched_law_article_cnt++; 
        }
        if (count($law_contents) < 5 ) {
            $law_contents[] = $law_content;
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
$this->title = "搜尋：「{$q}」";
?>
<?= $this->partial('common/header', $this) ?>
 <div class="main">
   <section class="page-hero search-form">
     <div class="container container-sm">
       <form action="/search" method="get">
         <div class="brand-name">
           LawTrace
         </div>
         <h2>
           你想暸解什麼法律的沿革或討論現狀？
         </h2>
         <div>
           <input type="search" name="q" class="form-control search-input" placeholder="關鍵字">
         </div>
         <div class="text-end">
           <button type="submit" class="btn btn-primary">
             搜尋
           </button>
         </div>
       </form>
     </div>
   </section>
   <div class="main-content">
     <section class="search-result">
       <div class="container container-sm">
         <div class="result-info">
           <div>
             <em><?= count($laws) ?> 個法律</em>中含有 <em><?= $matched_law_article_cnt ?> 筆法條內容</em>符合您關鍵字的搜尋結果。
           </div>
           <div>
             請點選個法律查看詳細法條內容、法律歷程、相關議案等內容。
           </div>
         </div>
         <?php foreach ($laws as $law) { ?>
           <div class="search-result-card">
             <div class="law-info">
               <div class="title">
                 <a href="/law/show/<?= $this->escape($law->法律編號) ?>">
                   <?= $this->escape($law->名稱); ?>
                 </a>
               </div>
               <?php $aliases = array_merge($law->其他名稱 ?? [], $law->別名 ?? []); ?>
               <?php if (!empty($aliases)) { ?>
                 <div class="alias">其他名稱： <?= $this->escape(implode('、', $aliases)) ?> </div>
               <?php } ?>
               <div class="update-date"><?= $this->escape($law->最新版本->版本編號 ?? '') ?></div>
             </div>
             <div class="law-list">
               <?php $law_contents = $law->law_contents; ?>
               <?php if (!empty($law_contents)) { ?>
                 <?php foreach ($law_contents as $law_content) { ?>
                   <div class="law-item">
                     <div><?= $this->escape($law_content->條號?? '') ?></div>
                     <div><?= nl2br(strip_tags($law_content->{'內容:highlight'}[0], '<em>')) ?></div>
                   </div>
                 <?php } ?>
               <?php } ?>
             </div>
           </div>
         <?php } ?>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
