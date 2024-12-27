<?php
$chapters = $this->_data['chapters'];
$chapter_units = $this->_data['chapter_units'];
$layer_cnt = count($chapter_units);
$layers = array_fill(0, $layer_cnt, []);

$dom = new DOMDocument('1.0', 'UTF-8');
foreach ($chapters as $chapter) {
    $chapter_name = $chapter->章名;
    $unit = LawChapterHelper::getChapterUnit($chapter_name);
    $layer_idx = array_search($unit, $chapter_units);

    //linked_idx 上一層章節中，所屬的章名位置
    $linked_idx = -1;
    if ($layer_idx != 0) {
        $upper_layer = $layers[$layer_idx - 1];
        $linked_idx = count($upper_layer) - 1;
    }

    //create html elements
    $item_div = $dom->createElement('div');
    $item_div->setAttribute('class', 'menu-item');
    $head_div = $dom->createElement('div');
    $head_div->setAttribute('class', 'menu-head');
    $anchor = $dom->createElement('a');
    $anchor->setAttribute('href', '#layer' . ($layer_idx + 1) . '-index' . (count($layers[$layer_idx]) + 1));
    $anchor->textContent = $chapter_name;
    //append elements
    $head_div->appendChild($anchor);
    $item_div->appendChild($head_div);

    $layers[$layer_idx][] = (object) [
        'linked_idx' => $linked_idx,
        'item_div' => $item_div,
    ];
    //for debugging
    //$current_idx = count($layers[$layer_idx]) - 1;
    //echo "{$layer_idx} {$current_idx} {$linked_idx} $chapter_name<br>";
}

//assemble chapters into one html fragment
$side_div = $dom->createElement('div');
$side_div->setAttribute('class', 'side-menu');
$layers = array_reverse($layers);
foreach ($layers as $idx => $layer) {
    if (count($layers) == 1 or $idx == count($layers) - 1) {
        foreach ($layer as $item) {
            $side_div->appendChild($item->item_div);
        }
        continue;
    }
    $upper_layer = $layers[$idx + 1];
    foreach ($upper_layer as $upper_item_idx => $upper_item) {
        $child_items = array_filter($layer, function($item) use ($upper_item_idx) {
            return $item->linked_idx == $upper_item_idx;
        });
        if (!empty($child_items)) {
            $upper_body_div = $dom->createElement('div');
            $upper_body_div->setAttribute('class', 'menu-body');
            foreach ($child_items as $item) {
                $upper_body_div->appendChild($item->item_div);
            }
            $upper_item_div = $upper_item->item_div;
            $upper_icon = $dom->createElement('i');
            $upper_icon->setAttribute('class', 'bi bi-chevron-down icon');
            $upper_head_div = $upper_item_div->getElementsByTagName('div')->item(0);
            $upper_head_div->appendChild($upper_icon);
            $upper_item_div->appendChild($upper_body_div);
            $upper_item->item_div = $upper_item_div;
        }
    }
}
?>
<div class="side">
  <div class="law-sections">
    <div class="title">
      選擇章節
    </div>
    <?= $dom->saveHTML($side_div) ?>
  </div>
</div>
