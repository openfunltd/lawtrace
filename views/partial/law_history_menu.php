<?php
$history_groups = $this->_data['history_groups'];
$menu_groups = [];
$menu_group_types = [];
foreach ($history_groups as $history_group) {
    $id = $history_group->id;
    $group_type = explode('-', $id)[0];
    $group_type_idx = array_search($group_type, $menu_group_types);
    if ($group_type_idx === false) {
        $menu_group_types[] = $group_type;
        $menu_groups[] = [$history_group];
    } else {
        $menu_groups[$group_type_idx][] = $history_group;
    }
}
?>
<div class="history-menu">
  <?php foreach ($menu_groups as $idx => $menu_group) { ?>
    <div class="title">
      <?php if ($menu_group_types[$idx] == '未分類') { ?>
        未審查
      <?php } else { ?>
        <?= $this->escape($menu_group_types[$idx]) ?>
      <?php } ?>
    </div>
    <ul>
      <?php foreach ($menu_group as $history_group) { ?>
        <?php $id = $history_group->id ?>
        <?php if ($id != '未分類') { ?>
          <li>
            <a href="#<?= $this->escape($history_group->id) ?>">
              <?= $this->escape($history_group->group_title) ?>
              <small><?= $this->escape($history_group->review_date) ?></small>
            </a> 
          </li>
        <?php } else { ?>
          <?php foreach ($history_group->bill_log as $bill) { ?>
            <li>
              <a href="#<?= $this->escape($bill->bill_id) ?>">
                <?= $this->escape($bill->主提案) ?>版本
                <small><?= $this->escape($bill->會議民國日期v2) ?>提案</small>
              </a>
            </li>
          <?php } ?>
        <?php } ?>
      <?php } ?>
    </ul>
  <?php } ?>
</div>
