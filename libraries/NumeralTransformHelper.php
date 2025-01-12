<?php

class NumeralTransformHelper
{
    //僅支援 0 ~ 9999
    public static function zhtwToArabic($zhtw_num)
    {
        $allow_chars = array_merge(self::$big_units, self::$small_units, array_keys(self::$digit_map));
        $zhtw_chars = mb_str_split($zhtw_num);
        foreach ($zhtw_chars as $zhtw_char) {
            if (!in_array($zhtw_char, $allow_chars)) {
                return $zhtw_num;
            }
        }

        if ($zhtw_num == '零') {
            return 0;
        }

        //把用來標記跨單位的零都去掉 ex: 一千零一 => 一千一
        $zhtw_num = mb_ereg_replace('零', '', $zhtw_num);

        if ($zhtw_num == '十') {
            return 10;
        }

        $zhtw_chunk = $zhtw_num;
        $arabic_num = '';
        foreach (self::$small_units as $unit) {
            $split = explode($unit, $zhtw_chunk);
            $digit = '0';
            if (count($split) == 2) {
                $digit = self::$digit_map[$split[0]];
                $zhtw_chunk = $split[1];
            }
            $arabic_num .= $digit;
        }
        $arabic_num .= ($zhtw_chunk != '') ? self::$digit_map[$zhtw_chunk] : '0';

        return $arabic_num = intval($arabic_num);
    }

    private static $big_units = ['秭', '垓', '京', '兆', '億', '萬'];
    private static $small_units = ['千', '百', '十'];
    private static $digit_map = [
        '一' => '1',
        '二' => '2',
        '三' => '3',
        '四' => '4',
        '五' => '5',
        '六' => '6',
        '七' => '7',
        '八' => '8',
        '九' => '9',
    ];
}
