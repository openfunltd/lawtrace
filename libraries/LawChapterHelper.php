<?php

class LawChapterHelper
{
    public static function getChapterUnits($chapters)
    {
        $chapter_units = [];
        foreach ($chapters as $chapter) {
            $chapter_name = $chapter->章名;
            $chapter_unit = self::getChapterUnit($chapter_name);
            if (!in_array($chapter_unit, $chapter_units)) {
                $chapter_units[] = $chapter_unit;
            }
        }
        return $chapter_units;
    }

    public static function getChapterUnit($chapter_name)
    {
        $chapter_index = explode(" ", $chapter_name)[0];

        //for dealing with example: '第五章之二' -> '章'
        $chapter_index = explode("之", $chapter_index)[0];

        $chapter_unit = mb_substr($chapter_index, -1, 1);
        return $chapter_unit;
    }
}
