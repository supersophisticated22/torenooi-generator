<?php

/**
 * Holds a type and is able to parse an integer number to a result string
 * depending on the type.
 *
 * @author Jurrien
 */
class RankType
{
    /** static array for num to type translation */
    private static $types = [0 => 'time', 1 => 'points', 2 => 'distance'];

    /** static translation for types to dutch */
    private static $typesNL = [0 => 'Tijd', 1 => 'Punten', 2 => 'Afstand'];

    /** number which hold the ranking type */
    private $myType;

    /**
     * Constructor sets up the type for ranking.
     *
     * @param  type  $type
     */
    public function RankType($type)
    {
        if (gettype($type) == 'string') {
            $this->myType = array_search($type, self::$types);

            if (! $this->myType) {
                $this->myType = intval($type);
            }

        } elseif (gettype($type) == 'integer') {
            $this->myType = $type;
        }

        if (($this->myType === false) || ($this->myType > count(self::$types))) {
            exit('Invalid type in RankType, type = '.gettype($type));
        }
    }

    /**
     * Returns the typestring.
     *
     * @return type string
     */
    public function getTypeStr()
    {
        return self::$types[$this->myType];
    }

    /**
     * Returns the ductch translation of the type string.
     *
     * @return dutch type string
     */
    public function getTypeStrNL()
    {
        return self::$typesNL[$this->myType];
    }

    /**
     * Returns the number of the type is the static type array.
     *
     * @return type number
     */
    public function getTypeNum()
    {
        return $this->myType;
    }

    private function extentString($str, $len)
    {
        while (strlen($str) < $len) {
            $str .= '0';
        }

        return $str;
    }

    /**
     * Function parses a string containing a sloppy time string into a number
     *
     * @param  string  $str  sloppy timestr
     * @return int representing time in ms
     */
    private function parsetime($str)
    {
        $tstr = str_replace('.', ':', $str);
        //        echo "$tstr</br>";

        $t = explode(':', $tstr);
        //        var_dump($t);
        switch (count($t)) {
            case 1: /** sec */
                $time = intval($t[0]) * 1000;
                break;
            case 2: /** min:sec */
                $time = (intval($t[0]) * 60000) + (intval($this->extentString($t[1], 2)) * 1000);
                break;
            case 3: /** min:sec:msec */
                $time = (intval($t[0]) * 60000);
                $time += intval($this->extentString($t[1], 2)) * 1000;
                $time += intval($this->extentString($t[2], 3));
                break;
            case 4: /** hour:min:sec:msec */
                $time = (intval($t[0]) * 3600000);
                $time += (intval($this->extentString($t[1], 2)) * 60000);
                $time += intval($this->extentString($t[2], 2)) * 1000;
                $time += intval($this->extentString($t[3], 3));
                break;
            default: exit('invalid time string');
        }

        //        echo $time . "</br>";
        return $time;
    }

    /**
     * Function converts a time number in ms to a string holding a time format.
     *
     * @param  type  $time  in ms
     * @return type formated time string
     */
    private function pasrseTimeToString($t)
    {
        $time = intval($t);
        $h = intval($time / 3600000);
        $m = intval(($time % 3600000) / 60000);
        $s = intval(($time % 60000) / 1000);
        $ms = intval(($time % 1000));

        //        echo "$time: $h || $m || $s || $ms </br>";

        if ($h > 0) {
            $format = '%02d:%02d:%02d:%02d';

            return sprintf($format, $h, $m, $s, $ms);
        } elseif ($ms > 0) {
            $format = '0:%02d:%02d:%02d';

            return sprintf($format, $m, $s, $ms);
        } elseif ($m > 0) {
            $format = '0:%02d:%02d:00';

            return sprintf($format, $m, $s);
        } else {
            $format = '0:00:%d:00';

            return sprintf($format, $s);
        }
    }

    /**
     * Function parses $num to a string holding holding the result, format
     * depends on the type.
     * * (note points has a precision of a 10th),
     * (note distance should be in mm and is returned in m)
     *
     * @param  $num  is a time stamp, points or distance (in mm)
     * @return string holding the result
     */
    public function parseFromNum($num)
    {
        switch ($this->myType) {
            case 0:
                // $str = date("H:i:s:u", $num);
                $str = $this->pasrseTimeToString($num);
                //                echo "$str</br>";
                break;
            case 1:
                $str = (string) ($num / 10);
                break;
            case 2:
                $str = (string) ($num / 1000); /** in mm */
                break;
        }

        return $str;
    }

    /**
     * Function parses a string depending on the type to a integer value.
     * (note points has a precision of a 10th),
     * (note distance should be in m and is returnd in mm)
     *
     * @param  $str  is the result string.
     * @return int a time stampt, point number or a distance depending on the type
     */
    public function parseToNum($str)
    {
        switch ($this->myType) {
            case 0:
                $i = $this->parsetime($str);
                break;
            case 1:
                $i = intval(floatval($str) * 10);
                break;
            case 2:
                $i = intval(floatval(str_replace(',', '.', $str)) * 1000); /** in mm */
                break;
        }

        return $i;
    }

    /**
     * Returns an array holding the dutch type names, key is the type number.
     *
     * @return array holding the type names in dutch.
     */
    public function getTypeList()
    {
        return self::$typesNL;
    }
}
