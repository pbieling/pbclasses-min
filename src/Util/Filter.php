<?php

namespace PbClasses\Util;

/**
 *
 * @author Peter Bieling - p-bieling.de
 * Apply one or more functions on the values of arrays to modify, escape or sanitze values
 */
class Filter {

    protected static $filterArr;

    public static function assocArr($arr, $filters = 'SPECIAL_CHARS') {
        $backArr = [];
        self::setFilters($filters);
        foreach ($arr as $key => $val) {
            $backArr[$key] = self::applyFilters($val);
        }
        return $backArr;
    }

    public static function numericArr($arr, $filters = 'SPECIAL_CHARS') {
        $backArr = [];
        self::setFilters($filters);
        for ($i = 0; $i < count($arr); $i++) {
            $backArr[$i] = self::applyFilters($arr[$i]);
        }
        return $backArr;
    }

    public static function str($str, $filters = 'SPECIAL_CHARS') {
        self::setFilters($filters);
        return self::applyFilters($str);
    }

    protected static function setFilters($filters) {
        self::$filterArr = []; //Reset
        $arr = explode(',', $filters);
        foreach ($arr as $sg) {
            $tmp = trim($sg);
            if ($tmp === '') {
                continue;
            }
            self::$filterArr[] = strtoupper($tmp);
        }
    }

    protected static function applyFilters($val) {
        foreach (self::$filterArr as $sgFilter) {
            $val = self::applySgFilter($val, $sgFilter);
        }
        return $val;
    }

    protected static function applySgFilter($val, $sgFilter) {
        $filterArr = \explode(':', $sgFilter);
        $fil = $filterArr[0];
        $flag = (isset($filterArr[1])) ? $filterArr[1] : null;

        switch ($fil) {
            case 'SPECIAL_CHARS':
            case 'HTMLSPECIALCHARS': //alias
                return htmlspecialchars($val);
            case 'TRIM':
                return trim($val);
            case 'ADDSLASHES':
                return addslashes($val);
            case 'STRTOUPPER':
                return strtoupper($val);
            case 'STRTOLOWER':
                return strtolower($val);
            case 'UTF8_DECODE':
                return utf8_decode($val);
            case 'UTF8_ENCODE':
                return utf8_encode($val);
            case 'URLDECODE':
                return urldecode($val);
            case 'URLENCODE':
                return urlencode($val);

            default:
                if (!$flag) {
                    //new \PbClasses\Debug\Logging(constant($fil), __FILE__, __LINE__);
                    return filter_var($val, constant($fil));
                } else {
                    //@todo: Bitmask with more than one constant
                    return filter_var($val, constant($fil), constant($flag));
                }
        }
    }
}
