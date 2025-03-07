<?php
$dates = [];
foreach ($this->data->meet->日期 as $d) {
    $dates[] = LawVersionHelper::getMinguoDate($d);
}

?>
<?= $this->partial('common/header', $this) ?>
 <div class="main">
   <section class="page-hero search-form">
     <div class="container container-sm">
       <nav class="breadcrumb-wrapper">
         <ol class="breadcrumb">
           <li class="breadcrumb-item">
             <a href="/">
               <i class="bi bi-house-door"></i>
             </a>
           </li>
           <li class="breadcrumb-item active">
             院會一讀議案
           </li>
           <li class="breadcrumb-item active">
             <?= $this->escape($this->data->meet->會議標題) ?>
           </li>
         </ol>
       </nav>
       <h2 class="light">
         <?= $this->escape($this->data->meet->會議標題) ?>
       </h2>
       <div class="info">
           <div class="review-date">
               會議日期：<?= $this->escape(implode('、', $dates)) ?>
           </div>
       </div>
       <div class="btn-group law-pages">
         <a href="<?= $this->escape($this->data->meet->會議資料[0]->ppg_url) ?>" class="btn btn-outline-primary">
           會議原始資料
           <i class="bi bi-box-arrow-up-right"></i>
         </a>
       </div>
     </div>
   </section>
   <div class="main-content">
     <section class="search-result">
       <div class="container container-sm">
         <?php foreach ($this->data->laws as $law) { ?>
           <div class="search-result-card">
             <div class="law-info">
               <div class="title">
                 <a href="/law/show/<?= $this->escape($law->data->法律編號) ?>">
                   <?= $this->escape($law->data->名稱); ?>
                 </a>
               </div>
               <?php $aliases = array_merge($law->data->其他名稱 ?? [], $law->data->別名 ?? []); ?>
               <?php if (!empty($aliases)) { ?>
                 <div class="alias">其他名稱： <?= $this->escape(implode('、', $aliases)) ?> </div>
               <?php } ?>
               <div class="update-date"><?= $this->escape($law->data->最新版本->版本編號 ?? '') ?></div>
             </div>
             <div>
               <div class="timeline-item">
                 <div class="item-body">
                   <div class="history-grid">
                     <div class="grid-head">
                       相關議案及其提案之條文
                     </div>
                     <div class="grid-body">
                       <?php foreach ($law->bills as $billNo) { ?>
                       <?php $bill = $this->data->bills[$billNo] ?>
                         <div class="grid-row">
                           <div class="party-img">
                             <?php if ($bill->party_img_path ?? false) { ?>
                             <img src="<?= $bill->party_img_path ?>">
                             <?php } ?>
                           </div>
                           <div class="party"><?= $this->escape($bill->主提案) ?></div>
                           <div class="sections">
                               <?= $this->escape($bill->議案名稱) ?><br>
                           提案人：<?php foreach ($bill->提案人 as $p) { ?>
                           <?php $img = PartyHelper::getImageByTermAndName($bill->屆, $p); ?>
                           <?php if ($img) { ?>
                           <img src="<?= $img ?>" alt="<?= $this->escape($p) ?>">
                           <?php } ?>
                           <?= $this->escape($p) ?> &nbsp;
                           <?php } ?><br>
                           連署人：<?php foreach ($bill->連署人 as $p) { ?>
                           <?php $img = PartyHelper::getImageByTermAndName($bill->屆, $p); ?>
                           <?php if ($img) { ?>
                           <img src="<?= $img ?>" alt="<?= $this->escape($p) ?>">
                           <?php } ?>
                           <?= $this->escape($p) ?> &nbsp;
                           <?php } ?>
                           </div>
                           <div class="details">
                             <a href="/law/compare?source=bill:<?= $billNo ?>" target="_blank">
                               議案詳細資訊
                               <i class="bi bi-arrow-right"></i>
                             </a>
                           </div>
                         </div>
                       <?php } ?>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
           </div>
         <?php } ?>
      </div>
    </section>
  </div>
</div>
<?= $this->partial('common/footer') ?>
