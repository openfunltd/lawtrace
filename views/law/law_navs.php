<?php
$tabs = [
    'diff' => ['異動條文'],
    'history' => ['經歷過程'],
];
?>
<ul class="nav nav-tabs">
  <?php foreach ($tabs as $key => $tab) { ?>
    <?php if (false === $this->{"nav_link_{$key}"}) { continue; } ?>
    <li class="nav-item">
      <a 
        class="nav-link <?= $this->if($key == $this->law_nav, 'active') ?>"
        href="<?= $this->escape($this->{"nav_link_{$key}"} ?? '#') ?>"
        ><?= $this->escape($tab[0]) ?></a>
    </li>
  <?php } ?>
</ul>


