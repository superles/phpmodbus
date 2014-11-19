<?php
/**
 * Phpmodbus Copyright (c) 2004, 2012 Jan Krakora
 *
 * This source file is subject to the "PhpModbus license" that is bundled
 * with this package in the file license.txt.
 *
 * @author Jan Krakora
 * @copyright Copyright (c) 2004, 2012 Jan Krakora
 * @license PhpModbus license
 * @category Phpmodbus
 * @package Phpmodbus
 * @version $id$
 *
 */

/**
 * PhpType
 *
 * The class includes set of methods that convert the received Modbus data
 * (array of bytes) to the PHP data type, i.e. signed int, unsigned int and float.
 *
 * @author Jan Krakora
 * @copyright  Copyright (c) 2004, 2012 Jan Krakora
 * @package Phpmodbus
 *
 */
class PhpType {

    /**
     * bytes2float
     *
     * The function converts array of 4 bytes to float. The return value
     * depends on order of the input bytes (endianning).
     *
     * @param array $values
     * @param bool $endianness
     * @return float
     */
    public static function bytes2float($values, $endianness = 0) {
        $data = array();
        $real = 0;

        // Set the array to correct form
        $data = self::checkData($values);
        // Combine bytes
        $real = self::combineBytes($data, $endianness);
        // Convert the real value to float
        return (float) self::real2float($real);
    }

    /**
     * bytes2signedInt
     *
     * The function converts array of 2 or 4 bytes to signed integer.
     * The return value depends on order of the input bytes (endianning).
     *
     * @param array $values
     * @param bool $endianness
     * @return int
     */
    public static function bytes2signedInt($values, $endianness = 0) {
        $data = array();
        $int = 0;
        // Set the array to correct form
        $data = self::checkData($values);
        // Combine bytes
        $int = self::combineBytes($data, $endianness);
        // In the case of signed 2 byte value convert it to 4 byte one
        if ((count($values) == 2) && ((0x8000 & $int) > 0)) {
            $int = 0xFFFF8000 | $int;
        }
        // Convert the value
        return (int) self::dword2signedInt($int);
    }

    /**
     * bytes2unsignedInt
     *
     * The function converts array of 2 or 4 bytes to unsigned integer.
     * The return value depends on order of the input bytes (endianning).
     *
     * @param array $values
     * @param bool $endianness
     * @return int|float
     */
    public static function bytes2unsignedInt($values, $endianness = 0) {
        $data = array();
        $int = 0;
        // Set the array to correct form
        $data = self::checkData($values);
        // Combine bytes
        $int = self::combineBytes($data, $endianness);
        // Convert the value
        return self::dword2unsignedInt($int);
    }

    /**
     * bytes2string
     *
     * The function converts an values array to the string. The function detects
     * the end of the string by 0x00 character as defined by string standards.
     *
     * @param array $values
     * @param bool $endianness
     * @return string
     */
    public static function bytes2string($values, $endianness = 0) {
        // Prepare string variable
        $str = "";
        // Parse the received data word array
        for($i=0;$i<count($values);$i+=2) {
            if ($endianness) {
                if($values[$i] != 0)
                    $str .= chr($values[$i]);
                else
                    break;
                if($values[$i+1] != 0)
                    $str .= chr($values[$i+1]);
                else
                    break;
            }
            else {
                if($values[$i+1] != 0)
                    $str .= chr($values[$i+1]);
                else
                    break;
                if($values[$i] != 0)
                    $str .= chr($values[$i]);
                else
                    break;
            }
        }
        // return string
        return $str;
    }

    /**
     * real2float
     *
     * This function converts a value in IEC-1131 REAL single precision form to float.
     *
     * For more see [{@link http://en.wikipedia.org/wiki/Single_precision Single precision on Wiki}] or
     * [{@link http://de.php.net/manual/en/function.base-convert.php PHP base_convert function commentary}, Todd Stokes @ Georgia Tech 21-Nov-2007] or
     * [{@link http://www.php.net/manual/en/function.pack.php PHP pack/unpack functionality}]
     *
     * @param value value in IEC REAL data type to be converted
     * @return float float value
     */
    private static function real2float($value) {
        // get unsigned long
        $ulong = pack("L", $value);
        // set float
        $float = unpack("f", $ulong);
        
        return $float[1];
    }

    /**
     * dword2signedInt
     *
     * Switch double word to signed integer
     *
     * @param int $value
     * @return int
     */
    private static function dword2signedInt($value) {
        if ((0x80000000 & $value) != 0) {
            return -(0x7FFFFFFF & ~$value)-1;
        } else {
            return (0x7FFFFFFF & $value);
        }
    }

    /**
     * dword2signedInt
     *
     * Switch double word to unsigned integer
     *
     * @param int $value
     * @return int|float
     */
    private static function dword2unsignedInt($value) {
        if ((0x80000000 & $value) != 0) {
            return ((float) (0x7FFFFFFF & $value)) + 2147483648;
        } else {
            return (int) (0x7FFFFFFF & $value);
        }
    }

    /**
     * checkData
     *
     * Check if the data variable is array, and check if the values are numeric
     * Returns array with exactly 2 or 4 byte elements or throws exception
     *
     * @param int $data
     * @param int $offset
     * @return array
     */
     static function checkData($data, $offset=FALSE) {
        // Check the data
        // If $offset===FALSE, $data must be array of exactly 2 or 4 bytes
        // If $offset >= 0, $data must be array of exactly $offset+2 or $offset+4 bytes, or >$offset+4 bytes in length
        // $n is the number of "usable" bytes in $data starting at the offset
        $n = is_array($data) ? count($data) : 0;
        // If $offset defined, $n>4 is allowed. Set $n=min($n,4) here so it doesn't trigger >4 error later
        if ($offset !== FALSE) $n = min($n,4);  
        if (($n <> 2) && ($n <> 4)) {
            throw new Exception('The input data should be an array of 2 or 4 bytes.');
        }
        
        $res = array();
        if ($offset===FALSE) $offset=0;
        if ($offset % 4) throw new Exception('Offset must be a multiple of 4.');

        for ($i=0; $i<$n; $i++) {
            $j =@ $data[$offset+$i];
            if ($j === NULL) throw new Exception('Array keys are not indexed by 0,1,2 and 3');
            if (!is_integer($j) || ($j<0) || ($j>255)) throw new Exception('Data are not integers in range 0-255');
            $res[$i] = $j;
        }
        return $res;
/*
        if (!is_array($data) ||
                count($data)<($ofs+2) ||
                count($data)>4 ||
                count($data)==3) {
            throw new Exception('The input data should be an array of 2 or 4 bytes.');
        }   
        // Fill the rest of array by zeroes
        if (count($data) == 2) {
            $data[2] = 0;
            $data[3] = 0;
        }
        // Check the values to be number
        if (!is_numeric($data[0]) ||
                !is_numeric($data[1]) ||
                !is_numeric($data[2]) ||
                !is_numeric($data[3])) {
            throw new Exception('Data are not numeric or the array keys are not indexed by 0,1,2 and 3');
        }

        return $data;
        */
    }

    /**
     * combineBytes
     *
     * Combine bytes together
     *
     * @param int $data
     * @param bool $endianness
     * @return int
     */
    private static function combineBytes($data, $endianness) {
        $value = 0;
        // Combine bytes
        if ($endianness == 0)
            $value = (($data[3] & 0xFF)<<16) |
                    (($data[2] & 0xFF)<<24) |
                    (($data[1] & 0xFF)) |
                    (($data[0] & 0xFF)<<8);
        else
            $value = (($data[3] & 0xFF)<<24) |
                    (($data[2] & 0xFF)<<16) |
                    (($data[1] & 0xFF)<<8) |
                    (($data[0] & 0xFF));

        return $value;
    }
}
?>