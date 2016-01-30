<?php

/**
 * Helper Plugin: Parse persian numbers to integer
 *
 * @license GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author  Masoud Sadrnezhaad <masoud@sadrnezhaad.ir>
 */
class helper_plugin_judge_numbers extends DokuWiki_Plugin
{

    public function parseNumber($number)
    {
        for ($i = 0; $i < mb_strlen($number, "UTF-8"); $i++) {
            $digits[$i] = $this->ordutf8(mb_substr($number, $i, 1, "UTF-8"));
            if (1776 <= $digits[$i] && $digits[$i] < 1786) {
                $digits[$i] = $digits[$i] - 1776 + 48;
            }
            $digits[$i] = chr($digits[$i]);
        }
        return implode($digits);
    }

    public function ordutf8($string)
    {
        $offset = 0;
        $code = ord(substr($string, 0, 1));
        if ($code >= 128) {
            if ($code < 224) {
                $bytesnumber = 2;
            } else if ($code < 240) {
                $bytesnumber = 3;
            } else if ($code < 248) {
                $bytesnumber = 4;
            }
            $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
            for ($i = 2; $i <= $bytesnumber; $i++) {
                $offset++;
                $code2 = ord(substr($string, $offset, 1)) - 128;
                $codetemp = $codetemp * 64 + $code2;
            }
            $code = $codetemp;
        }
        $offset += 1;
        if ($offset >= strlen($string)) {
            $offset = -1;
        }
        return $code;
    }
}