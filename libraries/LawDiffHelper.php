<?php

use cogpowered\FineDiff\Diff;

class LawDiffHelper
{
    public static function lawDiff($bill)
    {
        //對照表會有沒有 rows 的狀況 bill_SN: 20委11005501
        if (! property_exists($bill->對照表[0], 'rows')) {
            $commits = $bill->對照表[1]->rows;
        } else {
            $commits = $bill->對照表[0]->rows;
        }

        $fine_diff = new Diff();
        $diff = new stdClass();
        foreach ($commits as $commit) {
            $law_idx = self::getLawIndex($commit);
            $isNewLawIndex = (
                (property_exists($commit, '現行') && $commit->現行 != '') ||
                (property_exists($commit, '現行法') && $commit->現行法 != '')
            );
            $diff->{$law_idx} = new stdClass();
            $diff->{$law_idx}->current = ($isNewLawIndex) ? $commit->現行 : null;
            $diff->{$law_idx}->commit = (property_exists($commit, '修正')) ? $commit->修正 : $commit->增訂;
            if (isset($diff->{$law_idx}->current)) {
                $diff_html = $fine_diff->render($diff->{$law_idx}->current, $diff->{$law_idx}->commit);
                $diff->{$law_idx}->diff = preg_replace('/\\\\n/', "\n", $diff_html);
            }
            $diff->{$law_idx}->reason = (property_exists($commit, '說明')) ? $commit->說明 : null;
        }
        return $diff;
    }

    private static function getLawIndex($commit)
    {
        if (property_exists($commit, '現行') && $commit->現行 != '') {
            $text = $commit->現行;
        } else if (property_exists($commit, '現行法' && $commit->現行法 != '')) {
            $text = $commit->現行;
        } else if (property_exists($commit, '修正')) {
            $text = $commit->修正;
        } else {
            $text = $commit->增訂;
        }
        $text = str_replace('　', ' ', $text);
        return explode(' ', $text)[0];
    }
}
