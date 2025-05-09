<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Utils
{
    /**
    * @var array
    */
    public static $aSuppostedCharsets = array(
        'iso-8859-1', 'iso-8859-2', 'iso-8859-3', 'iso-8859-4', 'iso-8859-5', 'iso-8859-6',
        'iso-8859-7', 'iso-8859-8', 'iso-8859-9', 'iso-8859-10', 'iso-8859-11', 'iso-8859-12',
        'iso-8859-13', 'iso-8859-14', 'iso-8859-15', 'iso-8859-16',
        'koi8-r', 'koi8-u', 'koi8-ru',
        'cp1250', 'cp1251', 'cp1252', 'cp1253', 'cp1254', 'cp1257', 'cp949', 'cp1133',
        'cp850', 'cp866', 'cp1255', 'cp1256', 'cp862', 'cp874', 'cp932', 'cp950', 'cp1258',
        'windows-1250', 'windows-1251', 'windows-1252', 'windows-1253', 'windows-1254', 'windows-1255',
        'windows-1256', 'windows-1257', 'windows-1258', 'windows-874',
        'macroman', 'maccentraleurope', 'maciceland', 'maccroatian', 'macromania', 'maccyrillic',
        'macukraine', 'macgreek', 'macturkish', 'macintosh', 'machebrew', 'macarabic',
        'euc-jp', 'shift_jis', 'iso-2022-jp', 'iso-2022-jp-2', 'iso-2022-jp-1',
        'euc-cn', 'gb2312', 'hz', 'gbk', 'gb18030', 'euc-tw', 'big5', 'big5-hkscs',
        'iso-2022-cn', 'iso-2022-cn-ext', 'euc-kr', 'iso-2022-kr', 'johab',
        'armscii-8', 'georgian-academy', 'georgian-ps', 'koi8-t',
        'tis-620', 'macthai', 'mulelao-1',
        'viscii', 'tcvn', 'hp-roman8', 'nextstep',
        'utf-8', 'ucs-2', 'ucs-2be', 'ucs-2le', 'ucs-4', 'ucs-4be', 'ucs-4le',
        'utf-16', 'utf-16be', 'utf-16le', 'utf-32', 'utf-32be', 'utf-32le', 'utf-7',
        'c99', 'java', 'ucs-2-internal', 'ucs-4-internal');

    /**
    * @var string
    */
    public static $sTimeZone = null;

    /**
     * @return float
     */
    public static function Microtime()
    {
        return microtime(true);
    }

    /**
     * @param array $aArray
     * @param string $sKey
     * @param mixed $mDefault
     * @return mixed
     */
    public static function ArrayValue($aArray, $sKey, $mDefault)
    {
        return (isset($aArray[$sKey])) ? $aArray[$sKey] : $mDefault;
    }

    /**
     * @param array $aArray
     * @return bool
     */
    public static function IsAssocArray($aArray)
    {
        return in_array(false, array_map('is_int', array_keys($aArray)));
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function EncodeSpecialXmlChars($sValue)
    {
        return str_replace('>', '&gt;', str_replace('<', '&lt;', str_replace('&', '&amp;', $sValue)));
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function DecodeSpecialXmlChars($sValue)
    {
        return str_replace('&amp;', '&', str_replace('&lt;', '<', str_replace('&gt;', '>', $sValue)));
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function EncodeSimpleSpecialXmlChars($sValue)
    {
        return str_replace(']]>', '&#93;&#93;&gt;', $sValue);
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function DecodeSimpleSpecialXmlChars($sValue)
    {
        return str_replace('&#93;&#93;&gt;', ']]>', $sValue);
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function ShowCRLF($sValue)
    {
        return str_replace(array("\r", "\n", "\t"), array('\r', '\n', '\t'), $sValue);
    }

    /**
     * @param string $sPath
     * @param string $sPrefix = null
     * @return string
     */
    public static function GetFullPath($sPath, $sPrefix = null)
    {
        if ($sPrefix !== null && !self::IsFullPath($sPath)) {
            $sPath = rtrim($sPrefix, '\\/') . '/' . trim($sPath, '\\/');
        }

        if (@is_dir($sPath)) {
            $sPath = rtrim(str_replace('\\', '/', realpath($sPath)), '/');
        }

        return $sPath;
    }

    /**
     * @param string $sPpath
     * @return bool
     */
    public static function IsFullPath($sPpath)
    {
        if (strlen($sPpath) > 0) {
            return (($sPpath[0] == '/' || $sPpath[0] == '\\') || (strlen($sPpath) > 1 && self::IsWin() && $sPpath[1] == ':'));
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function IsWin()
    {
        return (defined('PHP_OS') && 'WIN' === strtoupper(substr(PHP_OS, 0, 3)));
    }

    /**
     * @param array $aArray
     * @param string $sType
     * @return array
     */
    public static function SetTypeArrayValue($aArray, $sType)
    {
        $aResult = array();
        foreach ($aArray as $mValue) {
            settype($mValue, $sType);
            $aResult[] = $mValue;
        }
        return $aResult;
    }

    /**
     * @param string $sPrefix
     * @return string
     */
    public static function ClearPrefix($sPrefix)
    {
        $sNewPrefix = preg_replace('/[^a-z0-9_]/i', '_', $sPrefix);
        if ($sNewPrefix !== $sPrefix) {
            $sNewPrefix = preg_replace('/[_]+/', '_', $sNewPrefix);
        }
        return $sNewPrefix;
    }

    /**
     * @param string $sEncoding
     * @return string
     */
    protected static function iconvNormalizeCharset($sEncoding)
    {
        $sEncoding = strtolower($sEncoding);
        switch ($sEncoding) {
            case 'ansi':
            case 'ansii':
            case 'us-ansii':
                $sEncoding = 'iso-8859-1';
                break;
            case 'utf8':
            case 'utf-8':
                $sEncoding = 'utf-8';
                break;
            case 'utf7-imap':
            case 'utf7imap':
            case 'utf-7imap':
            case 'utf-7-imap':
                $sEncoding = 'utf7-imap';
                break;
            case 'ks-c-5601-1987':
            case 'ks_c_5601-1987':
                $sEncoding = 'euc-kr';
                break;
            case 'x-gbk':
                $sEncoding = 'gb2312';
                break;
            case 'iso-8859-i':
            case 'iso-8859-8-i':
                $sEncoding = 'iso-8859-8';
                break;
        }

        return $sEncoding;
    }

    /**
     * @param string $sString
     * @param string $sFromEncoding
     * @param string $sToEncoding
     * @return string
     */
    public static function ConvertEncoding($sString, $sFromEncoding, $sToEncoding)
    {
        $sResult = $sString;
        $sFromEncoding = self::iconvNormalizeCharset($sFromEncoding);
        $sToEncoding = self::iconvNormalizeCharset($sToEncoding);

        if ('' === trim($sResult) || $sFromEncoding === $sToEncoding) {
            return $sResult;
        }

        switch (true) {
            default:
                break;
            case ($sFromEncoding === 'iso-8859-1' && $sToEncoding === 'utf-8' && function_exists('mb_convert_encoding')):
                $sResult = mb_convert_encoding($sResult, $sToEncoding, $sFromEncoding);
                break;
            case ($sFromEncoding === 'utf-8' && $sToEncoding === 'iso-8859-1' && function_exists('mb_convert_encoding')):
                $sResult = mb_convert_encoding($sResult, $sToEncoding, $sFromEncoding);
                break;
            case ($sFromEncoding === 'utf7-imap' && $sToEncoding === 'utf-8'):
                $sResult = self::Utf7ModifiedToUtf8($sResult);
                if (false === $sResult) {
                    $sResult = $sString;
                }
                break;
            case ($sFromEncoding === 'utf-8' && $sToEncoding === 'utf7-imap'):
                if (!self::IsUtf7($sResult)) {
                    $sResult = self::Utf8ToUtf7Modified($sResult);
                }
                if (false === $sResult) {
                    $sResult = $sString;
                }
                break;
            case (in_array(strtolower($sFromEncoding), self::$aSuppostedCharsets)):
                $sResult = @iconv($sFromEncoding, $sToEncoding . '//IGNORE', $sResult);
                if (false === $sResult) {
                    Api::Log('iconv FALSE result ["' . $sFromEncoding . '", "' . $sToEncoding . '//IGNORE", "' . substr($sString, 0, 20) . ' / cut"]', Enums\LogLevel::Error);
                    $sResult = $sString;
                }
                break;
        }

        return $sResult;
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function UrlSafeBase64Encode($sValue)
    {
        return \rtrim(\strtr(\base64_encode($sValue), '+/', '-_'), '=');
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function UrlSafeBase64Decode($sValue)
    {
        $sValue = \rtrim(\strtr($sValue, '-_', '+/'), '=');
        return \MailSo\Base\Utils::Base64Decode(\str_pad($sValue, \strlen($sValue) + (\strlen($sValue) % 4), '=', STR_PAD_RIGHT));
    }

    /**
     * @param string $sStr
     * @return bool
     */
    public static function IsUtf7($sStr)
    {
        $iAmp = strpos($sStr, '&');
        return (false !== $iAmp && false !== strpos($sStr, '-', $iAmp));
    }

    /**
     * @param string $str
     * @return string
     */
    public static function Utf7ModifiedToUtf8($str)
    {
        $array = array(-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,-1,62, 63,-1,-1,-1,52,53,54,55,56,57,58,59,60,61,-1,-1,-1,-1,-1,-1,-1,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,-1,-1,-1,-1,-1,-1,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,-1,-1,-1,-1,-1);

        $result = '';
        $error = false;
        $strlen = strlen($str);

        for ($i = 0; $strlen > 0; $i++, $strlen--) {
            $char = $str[$i];
            if ($char == '&') {
                $i++;
                $strlen--;

                $char = isset($str[$i]) ? $str[$i] : null;
                if ($char === null) {
                    break;
                }

                if ($strlen && $char == '-') {
                    $result .= '&';
                    continue;
                }

                $ch = 0;
                $k = 10;
                for (; $strlen > 0; $i++, $strlen--) {
                    $char = $str[$i];

                    $b = $array[ord($char)];
                    if ((ord($char) & 0x80) || $b == -1) {
                        break;
                    }

                    if ($k > 0) {
                        $ch |= $b << $k;
                        $k -= 6;
                    } else {
                        $ch |= $b >> (-$k);
                        if ($ch < 0x80) {
                            if (0x20 <= $ch && $ch < 0x7f) {
                                return $error;
                            }

                            $result .= chr($ch);
                        } elseif ($ch < 0x800) {
                            $result .= chr(0xc0 | ($ch >> 6));
                            $result .= chr(0x80 | ($ch & 0x3f));
                        } else {
                            $result .= chr(0xe0 | ($ch >> 12));
                            $result .= chr(0x80 | (($ch >> 6) & 0x3f));
                            $result .= chr(0x80 | ($ch & 0x3f));
                        }

                        $ch = ($b << (16 + $k)) & 0xffff;
                        $k += 10;
                    }
                }

                if (($ch || $k < 6) ||
                    (!$strlen || $char != '-') ||
                    ($strlen > 2 && '&' === $str[$i + 1] && '-' !==  $str[$i + 2])) {
                    return $error;
                }
            } elseif (ord($char) < 0x20 || ord($char) >= 0x7f) {
                return $error;
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function Utf8ToUtf7Modified($str)
    {
        $array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9','+',',');

        $strlen = strlen($str);
        $isB = false;
        $i = $n = 0;
        $return = '';
        $error = false;
        $ch = $b = $k = 0;

        while ($strlen) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $ch = $c;
                $n = 0;
            } elseif ($c < 0xc2) {
                return $error;
            } elseif ($c < 0xe0) {
                $ch = $c & 0x1f;
                $n = 1;
            } elseif ($c < 0xf0) {
                $ch = $c & 0x0f;
                $n = 2;
            } elseif ($c < 0xf8) {
                $ch = $c & 0x07;
                $n = 3;
            } elseif ($c < 0xfc) {
                $ch = $c & 0x03;
                $n = 4;
            } elseif ($c < 0xfe) {
                $ch = $c & 0x01;
                $n = 5;
            } else {
                return $error;
            }

            $i++;
            $strlen--;

            if ($n > $strlen) {
                return $error;
            }

            for ($j = 0; $j < $n; $j++) {
                $o = ord($str[$i + $j]);
                if (($o & 0xc0) != 0x80) {
                    return $error;
                }

                $ch = ($ch << 6) | ($o & 0x3f);
            }

            if ($n > 1 && !($ch >> ($n * 5 + 1))) {
                return $error;
            }

            $i += $n;
            $strlen -= $n;

            if ($ch < 0x20 || $ch >= 0x7f) {
                if (!$isB) {
                    $return .= '&';
                    $isB = true;
                    $b = 0;
                    $k = 10;
                }

                if ($ch & ~0xffff) {
                    $ch = 0xfffe;
                }

                $return .= $array[($b | $ch >> $k)];
                $k -= 6;
                for (; $k >= 0; $k -= 6) {
                    $return .= $array[(($ch >> $k) & 0x3f)];
                }

                $b = ($ch << (-$k)) & 0x3f;
                $k += 16;
            } else {
                if ($isB) {
                    if ($k > 10) {
                        $return .= $array[$b];
                    }
                    $return .= '-';
                    $isB = false;
                }

                $return .= chr($ch);
                if ('&' === chr($ch)) {
                    $return .= '-';
                }
            }
        }

        if ($isB) {
            if ($k > 10) {
                $return .= $array[$b];
            }

            $return .= '-';
        }

        return $return;
    }

    /**
     * @param string $sValue
     * @return string
     */
    public static function EncryptValue($sValue)
    {
        $mKey = ctype_xdigit(Api::$sEncryptionKey) ? hex2bin(Api::$sEncryptionKey) : Api::$sEncryptionKey;
        $sEncryptedValue = \Aurora\System\Utils\Crypt::XxteaEncrypt($sValue, $mKey);
        return @trim(self::UrlSafeBase64Encode($sEncryptedValue));
    }

    /**
     * @param string $sEncryptedValue
     * @return string
     */
    public static function DecryptValue($sEncryptedValue)
    {
        $mKey = ctype_xdigit(Api::$sEncryptionKey) ? hex2bin(Api::$sEncryptionKey) : Api::$sEncryptionKey;
        $sEncryptedValue = self::UrlSafeBase64Decode(trim($sEncryptedValue));
        $sValue = \Aurora\System\Utils\Crypt::XxteaDecrypt($sEncryptedValue, $mKey);

        $sCryptKey = '$2y$07$' . Api::$sEncryptionKey . '$';

        if ($sValue === false) {
            $sValue = \Aurora\System\Utils\Crypt::XxteaDecrypt($sEncryptedValue, \md5($sCryptKey));
        }

        if ($sValue === false) {
            $sValue = \Aurora\System\Utils\Crypt::XxteaDecrypt($sEncryptedValue, $sCryptKey);
        }

        return $sValue;
    }

    /**
     * @param string $sEncryptedValue
     * @return string
     */
    public static function IsEncryptedValue($sEncryptedValue)
    {
        $sValue = \Aurora\System\Utils::DecryptValue($sEncryptedValue);
        return ($sValue === false || $sValue === '') ? false : true;
    }

    /**
     * @param string $sEmail
     * @return string
     */
    public static function GetAccountNameFromEmail($sEmail)
    {
        $sResult = '';
        if (!empty($sEmail)) {
            $iPos = strpos($sEmail, '@');
            $sResult = (false === $iPos) ? $sEmail : substr($sEmail, 0, $iPos);
        }

        return $sResult;
    }

    /**
     * @param int $iSizeInBytes
     * @return string
     */
    public static function GetFriendlySize($iSizeInBytes)
    {
        $iSizeInKB = ceil($iSizeInBytes / 1024);
        $iSizeInMB = $iSizeInKB / 1024;
        if ($iSizeInMB >= 100) {
            $iSizeInKB = ceil($iSizeInMB * 10 / 10) . 'MB';
        } elseif ($iSizeInMB > 1) {
            $iSizeInKB = (ceil($iSizeInMB * 10) / 10) . 'MB';
        } else {
            $iSizeInKB = $iSizeInKB . 'KB';
        }

        return $iSizeInKB;
    }

    /**
     * @param int $iSizeInBytes
     * @return string
     */
    public static function GetFriendlySizeSpec($iSizeInBytes)
    {
        $size = ceil($iSizeInBytes / 1024);
        $mbSize = $size / 1024;
        return ($mbSize > 1)
            ? (($mbSize >= 1024)
                ? (ceil(($mbSize * 10) / 1024) / 10) . 'GB'
                : (ceil($mbSize * 10) / 10) . 'MB')
            : $size . 'KB';
    }

    /**
     * @staticvar array $aMapping
     * @param int $iCodePage
     * @return string
     */
    public static function GetCodePageName($iCodePage)
    {
        static $aMapping = array(
            0 => 'default',
            51936 => 'euc-cn',
            936 => 'gb2312',
            950 => 'big5',
            946 => 'euc-kr',
            50225 => 'iso-2022-kr',
            50220 => 'iso-2022-jp',
            932 => 'shift-jis',
            65000 => 'utf-7',
            65001 => 'utf-8',
            1250 => 'windows-1250',
            1251 => 'windows-1251',
            1252 => 'windows-1252',
            1253 => 'windows-1253',
            1254 => 'windows-1254',
            1255 => 'windows-1255',
            1256 => 'windows-1256',
            1257 => 'windows-1257',
            1258 => 'windows-1258',
            20866 => 'koi8-r',
            28591 => 'iso-8859-1',
            28592 => 'iso-8859-2',
            28593 => 'iso-8859-3',
            28594 => 'iso-8859-4',
            28595 => 'iso-8859-5',
            28596 => 'iso-8859-6',
            28597 => 'iso-8859-7',
            28598 => 'iso-8859-8'
        );

        return (isset($aMapping[$iCodePage])) ? $aMapping[$iCodePage] : '';
    }

    /**
     * @staticvar array $aMapping
     * @param string $sCodePageName
     * @return int
     */
    public static function GetCodePageNumber($sCodePageName)
    {
        static $aMapping = array(
            'default' => 0,
            'euc-cn' => 51936,
            'gb2312' => 936,
            'big5' => 950,
            'euc-kr' => 949,
            'iso-2022-kr' => 50225,
            'iso-2022-jp' => 50220,
            'shift-jis' => 932,
            'utf-7' => 65000,
            'utf-8' => 65001,
            'windows-1250' => 1250,
            'windows-1251' => 1251,
            'windows-1252' => 1252,
            'windows-1253' => 1253,
            'windows-1254' => 1254,
            'windows-1255' => 1255,
            'windows-1256' => 1256,
            'windows-1257' => 1257,
            'windows-1258' => 1258,
            'koi8-r' => 20866,
            'iso-8859-1' => 28591,
            'iso-8859-2' => 28592,
            'iso-8859-3' => 28593,
            'iso-8859-4' => 28594,
            'iso-8859-5' => 28595,
            'iso-8859-6' => 28596,
            'iso-8859-7' => 28597,
            'iso-8859-8' => 28598
        );

        return (isset($aMapping[$sCodePageName])) ? $aMapping[$sCodePageName] : 0;
    }

    /**
     * @param string $sDateTime
     * @return array|bool
     */
    public static function DateParse($sDateTime)
    {
        if (function_exists('date_parse')) {
            return date_parse($sDateTime);
        }

        $mReturn = false;
        $aDateTime = explode(' ', $sDateTime, 2);
        if (count($aDateTime) == 2) {
            $aDate = explode('-', trim($aDateTime[0]), 3);
            $aTime = explode(':', trim($aDateTime[1]), 3);

            if (3 === count($aDate) && 3 === count($aTime)) {
                $mReturn = array(
                    'year' => $aDate[0],
                    'day' => $aDate[2],
                    'month' => $aDate[1],

                    'hour' => $aTime[0],
                    'minute' => $aTime[1],
                    'second' => $aTime[2]
                );
            }
        }
        return $mReturn;
    }

    /**
     * @param string $sTimeOffset
     * @return int
     */
    public static function GetTimeOffsetFromHoursString($sTimeOffset)
    {
        $iResult = 0;
        $sTimeOffset = trim($sTimeOffset);
        if (0 < strlen($sTimeOffset)) {
            $sSign = $sTimeOffset[0];
            $sTimeOffset = substr($sTimeOffset, 1);
            $nOffset = (is_numeric($sTimeOffset)) ? (int) $sTimeOffset : 0;

            $iHours = $nOffset / 100;
            $iMinutes = $nOffset % 100;

            $iMultiplier = ('-' === $sSign) ? -1 : 1;

            $iResult += $iMultiplier * $iHours * 60 * 60;
            $iResult += $iMultiplier * $iMinutes * 60;
        }

        return $iResult;
    }

    /**
     * @param int $iDefaultTimeZone
     * @param string $sClientTimeZone = ''
     * @return int
     */
    public static function GetTimeOffset($iDefaultTimeZone, $sClientTimeZone = '')
    {
        if ('' !== $sClientTimeZone) {
            try {
                $oDateTimeZone = new \DateTimeZone($sClientTimeZone);
                return $oDateTimeZone->getOffset(new \DateTime('now')) / 60;
            } catch (\Exception $oE) {
                Api::Log($sClientTimeZone);
                Api::LogObject($oE, Enums\LogLevel::Warning);
            }
        }

        $iTimeOffset = 0;
        switch ($iDefaultTimeZone) {
            default:
            case 0:
                break;
            case 1:
                $iTimeOffset = -12 * 60;
                break;
            case 2:
                $iTimeOffset = -11 * 60;
                break;
            case 3:
                $iTimeOffset = -10 * 60;
                break;
            case 4:
                $iTimeOffset = -9 * 60;
                break;
            case 5:
                $iTimeOffset =  -8 * 60;
                break;
            case 6:
            case 7:
                $iTimeOffset = -7 * 60;
                break;
            case 8:
            case 9:
            case 10:
            case 11:
                $iTimeOffset = -6 * 60;
                break;
            case 12:
            case 13:
            case 14:
                $iTimeOffset = -5 * 60;
                break;
            case 15:
            case 16:
            case 17:
                $iTimeOffset = -4 * 60;
                break;
            case 18:
                $iTimeOffset = -3.5 * 60;
                break;
            case 19:
            case 20:
            case 21:
                $iTimeOffset = -3 * 60;
                break;
            case 22:
                $iTimeOffset = -2 * 60;
                break;
            case 23:
            case 24:
                $iTimeOffset = -60;
                break;
            case 25:
            case 26:
                $iTimeOffset = 0;
                break;
            case 27:
            case 28:
            case 29:
            case 30:
            case 31:
                $iTimeOffset = 60;
                break;
            case 32:
            case 33:
            case 34:
            case 35:
            case 36:
            case 37:
                $iTimeOffset = 2 * 60;
                break;
            case 38:
            case 39:
            case 40:
                $iTimeOffset = 3 * 60;
                break;
            case 41:
                $iTimeOffset = 3.5 * 60;
                break;
            case 42:
            case 43:
            case 44:
                $iTimeOffset = 4 * 60;
                break;
            case 45:
                $iTimeOffset = 4.5 * 60;
                break;
            case 46:
                $iTimeOffset = 5 * 60;
                break;
            case 47:
                $iTimeOffset = 5.5 * 60;
                break;
            case 48:
                $iTimeOffset = 5 * 60 + 45;
                break;
            case 49:
            case 50:
            case 51:
            case 52:
                $iTimeOffset = 6 * 60;
                break;
            case 53:
                $iTimeOffset = 6.5 * 60;
                // no break
            case 54:
                $iTimeOffset = 7 * 60;
                break;
            case 55:
            case 56:
            case 57:
            case 58:
            case 59:
            case 60:
                $iTimeOffset = 8 * 60;
                break;
            case 61:
            case 62:
                $iTimeOffset = 9 * 60;
                break;
            case 63:
            case 64:
                $iTimeOffset = 9.5 * 60;
                break;
            case 65:
            case 66:
            case 67:
            case 68:
            case 69:
                $iTimeOffset = 10 * 60;
                break;
            case 70:
            case 71:
                $iTimeOffset = 11 * 60;
                break;
            case 72:
            case 73:
                $iTimeOffset = 12 * 60;
                break;
            case 74:
                $iTimeOffset = 13 * 60;
                break;
        }

        return $iTimeOffset;
    }

    /**
     * @param int $iDefaultTimeZone
     * @param string $sClientTimeZone = ''
     * @return string
     */
    public static function GetStrTimeZone($iDefaultTimeZone, $sClientTimeZone = '')
    {
        if ('' !== $sClientTimeZone) {
            return $sClientTimeZone;
        }

        if (null !== self::$sTimeZone) {
            return self::$sTimeZone;
        }

        $sResult = 'Etc/GMT';

        $aTimeZones = array(
            'Default', #0
            'Pacific/Kwajalein', #1
            'Pacific/Midway', #2
            'US/Hawaii', #3
            'US/Alaska', #4
            'America/Tijuana', #5
            'America/Dawson_Creek', #6
            'America/Denver', #7
            'America/Belize', #8
            'America/Chicago', #9
            'America/Cancun', #10
            'America/Belize', #11
            'America/Havana', #12
            'America/New_York', #13
            'America/Bogota', #14
            'America/Santiago', #15
            'America/Caracas', #16
            'America/Glace_Bay', #17
            'America/St_Johns', #18
            'America/Godthab', #19
            'America/Argentina/Buenos_Aires', #20
            'America/Sao_Paulo', #21
            'America/Noronha', #22
            'Atlantic/Cape_Verde', #23
            'Atlantic/Azores', #24
            'Africa/Abidjan', #25
            'Europe/Dublin', #26
            'Europe/Amsterdam', #27
            'Europe/Belgrade', #28
            'Europe/Brussels', #29
            'Europe/Sarajevo', #30
            'Africa/Algiers', #31
            'Europe/Minsk', #32
            'Europe/Bucharest', #33
            'Africa/Cairo', #34
            'Africa/Blantyre', #35
            'Africa/Harare', #36
            'Asia/Jerusalem', #37
            'Asia/Baghdad', #38
            'Asia/Kuwait', #39
            'Africa/Addis_Ababa', #40
            'Europe/Moscow', #41
            'Asia/Tehran', #42
            'Asia/Dubai', #43
            'Asia/Yerevan', #44
            'Asia/Kabul', #45
            'Asia/Tashkent', #46
            'Asia/Kolkata', #47
            'Asia/Katmandu', #48
            'Asia/Yekaterinburg', #49
            'Asia/Almaty', #50
            'Asia/Dhaka', #51
            'Asia/Colombo', #52
            'Asia/Rangoon', #53
            'Asia/Bangkok', #54
            'Asia/Krasnoyarsk', #55
            'Asia/Hong_Kong', #56
            'Asia/Irkutsk', #57
            'Asia/Kuala_Lumpur', #58
            'Australia/Perth', #59
            'Asia/Taipei', #60
            'Asia/Tokyo', #61
            'Asia/Seoul', #62
            'Australia/Adelaide', #63
            'Australia/Darwin', #64
            'Asia/Yakutsk', #65
            'Australia/Brisbane', #66
            'Australia/Canberra', #67
            'Pacific/Guam', #68
            'Australia/Hobart', #69
            'Asia/Vladivostok', #70
            'Pacific/Noumea', #71
            'Asia/Magadan', #72
            'Asia/Anadyr', #73
            'Pacific/Tongatapu' #74
        );

        $iDefaultTimeZone = isset($aTimeZones[$iDefaultTimeZone]) ? $iDefaultTimeZone : 0;

        if (0 === $iDefaultTimeZone) {
            $iOffset = self::GetTimeOffset($iDefaultTimeZone, $sClientTimeZone) / 60 * -1;
            $sSign = ($iOffset < 0) ? '-' : '+';
            $sResult = 'Etc/GMT' . $sSign . abs($iOffset);
        } else {
            $sResult = $aTimeZones[$iDefaultTimeZone];
        }

        self::$sTimeZone = $sResult;
        return self::$sTimeZone;
    }

    /**
     * @param string $sDir
     */
    public static function RecRmdir($sDir)
    {
        if (is_dir($sDir)) {
            $aObjects = scandir($sDir);
            foreach ($aObjects as $sObject) {
                if ($sObject != '.' && $sObject != '..') {
                    if (filetype($sDir . '/' . $sObject) == 'dir') {
                        self::RecRmdir($sDir . '/' . $sObject);
                    } else {
                        unlink($sDir . '/' . $sObject);
                    }
                }
            }

            reset($aObjects);
            rmdir($sDir);
        }
    }

    /**
     * @return bool
     */
    public static function IsPhp53()
    {
        return (bool) (version_compare(phpversion(), '5.3.0') > -1);
    }

    /**
     * @return bool
     */
    public static function HasGdSupport()
    {
        return (bool) @function_exists('imagecreatefrompng');
    }

    /**
     * @return bool
     */
    public static function hasSslSupport()
    {
        return (bool) @function_exists('openssl_open');
    }

    /**
     * @return bool
     */
    public static function IsMcryptSupported()
    {
        return (bool) @function_exists('mcrypt_encrypt');
    }

    /**
     * @return bool
     */
    public static function IsIconvSupported()
    {
        return (bool) @function_exists('iconv');
    }

    /**
     * @return bool
     */
    public static function IsGzipSupported()
    {
        return (bool)
            ((false !== strpos(isset($_SERVER['HTTP_ACCEPT_ENCODING'])
                ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '', 'gzip'))
            && function_exists('gzencode'));
    }

    /**
     * @param string $sFileName
     * @return string
     */
    public static function GetFileExtension($sFileName)
    {
        $iLast = strrpos($sFileName, '.');
        $sExtension = '';
        if ($iLast !== false) {
            $sExtension = substr($sFileName, $iLast + 1);
            if (strlen($sExtension) > 5) {
                $sExtension = '';
            }
        }
        return $sExtension;
    }

    public static function GetMimeContentTypes()
    {
        return array(

            'eml'	=> 'message/rfc822',
            'mime'	=> 'message/rfc822',
            'txt'	=> 'text/plain',
            'text'	=> 'text/plain',
            'def'	=> 'text/plain',
            'list'	=> 'text/plain',
            'in'	=> 'text/plain',
            'ini'	=> 'text/plain',
            'log'	=> 'text/plain',
            'sql'	=> 'text/plain',
            'cfg'	=> 'text/plain',
            'conf'	=> 'text/plain',
            'rtx'	=> 'text/richtext',
            'vcard'	=> 'text/vcard',
            'vcf'	=> 'text/vcard',
            'htm'	=> 'text/html',
            'html'	=> 'text/html',
            'csv'	=> 'text/csv',
            'ics'	=> 'text/calendar',
            'ifb'	=> 'text/calendar',
            'xml'	=> 'text/xml',
            'json'	=> 'application/json',
            'swf'	=> 'application/x-shockwave-flash',
            'hlp'	=> 'application/winhlp',
            'wgt'	=> 'application/widget',
            'chm'	=> 'application/vnd.ms-htmlhelp',
            'p10'	=> 'application/pkcs10',
            'p7c'	=> 'application/pkcs7-mime',
            'p7m'	=> 'application/pkcs7-mime',
            'p7s'	=> 'application/pkcs7-signature',
//			'ttf'	=> 'application/x-ttf',
            'torrent'	=> 'application/x-bittorrent',

            // scripts
            'js'	=> 'application/javascript',
            'pl'	=> 'text/perl',
            'css'	=> 'text/css',
            'asp'	=> 'text/asp',
            'php'	=> 'application/x-httpd-php',
            'php3'	=> 'application/x-httpd-php',
            'php4'	=> 'application/x-httpd-php',
            'php5'	=> 'application/x-httpd-php',
            'phtml'	=> 'application/x-httpd-php',

            // images
            'png'	=> 'image/png',
            'jpg'	=> 'image/jpeg',
            'jpeg'	=> 'image/jpeg',
            'jpe'	=> 'image/jpeg',
            'jfif'	=> 'image/jpeg',
            'gif'	=> 'image/gif',
            'bmp'	=> 'image/bmp',
            'cgm'	=> 'image/cgm',
            'ief'	=> 'image/ief',
            'ico'	=> 'image/x-icon',
            'tif'	=> 'image/tiff',
            'tiff'	=> 'image/tiff',
            'svg'	=> 'image/svg+xml',
            'svgz'	=> 'image/svg+xml',
            'djv'	=> 'image/vnd.djvu',
            'djvu'	=> 'image/vnd.djvu',
            'webp'	=> 'image/webp',

            // archives
            'zip'	=> 'application/zip',
            '7z'	=> 'application/x-7z-compressed',
            'rar'	=> 'application/x-rar-compressed',
            'exe'	=> 'application/x-msdownload',
            'dll'	=> 'application/x-msdownload',
            'scr'	=> 'application/x-msdownload',
            'com'	=> 'application/x-msdownload',
            'bat'	=> 'application/x-msdownload',
            'msi'	=> 'application/x-msdownload',
            'cab'	=> 'application/vnd.ms-cab-compressed',
            'gz'	=> 'application/x-gzip',
            'tgz'	=> 'application/x-gzip',
            'bz'	=> 'application/x-bzip',
            'bz2'	=> 'application/x-bzip2',
            'deb'	=> 'application/x-debian-package',

            // fonts
            'psf'	=> 'application/x-font-linux-psf',
            'otf'	=> 'application/x-font-otf',
            'pcf'	=> 'application/x-font-pcf',
            'snf'	=> 'application/x-font-snf',
            'ttf'	=> 'application/x-font-ttf',
            'ttc'	=> 'application/x-font-ttf',

            // audio
            'mp3'	=> 'audio/mpeg',
            'amr'	=> 'audio/amr',
            'aac'	=> 'audio/x-aac',
            'aif'	=> 'audio/x-aiff',
            'aifc'	=> 'audio/x-aiff',
            'aiff'	=> 'audio/x-aiff',
            'wav'	=> 'audio/x-wav',
            'wma'	=> 'audio/x-ms-wma',
            'wax'	=> 'audio/x-ms-wax',
            'midi'	=> 'audio/midi',
            'mp4a'	=> 'audio/mp4',
            'ogg'	=> 'audio/ogg',
            'weba'	=> 'audio/webm',
            'ra'	=> 'audio/x-pn-realaudio',
            'ram'	=> 'audio/x-pn-realaudio',
            'rmp'	=> 'audio/x-pn-realaudio-plugin',
            'm3u'	=> 'audio/x-mpegurl',

            // video
            'flv'	=> 'video/x-flv',
            'qt'	=> 'video/quicktime',
            'mov'	=> 'video/quicktime',
//			'wmv'	=> 'video/windows-media',
            'avi'	=> 'video/x-msvideo',
            'mpg'	=> 'video/mpeg',
            'mpeg'	=> 'video/mpeg',
            'mpe'	=> 'video/mpeg',
            'm1v'	=> 'video/mpeg',
            'm2v'	=> 'video/mpeg',
            '3gp'	=> 'video/3gpp',
            '3g2'	=> 'video/3gpp2',
            'h261'	=> 'video/h261',
            'h263'	=> 'video/h263',
            'h264'	=> 'video/h264',
            'jpgv'	=> 'video/jpgv',
            'mp4v'	=> 'video/mp4',
            'mpg4'	=> 'video/mp4',
            'ogv'	=> 'video/ogg',
            'webm'	=> 'video/webm',
            'm4v'	=> 'video/x-m4v',
            'asf'	=> 'video/x-ms-asf',
            'asx'	=> 'video/x-ms-asf',
            'wm'	=> 'video/x-ms-wm',
            'wmv'	=> 'video/x-ms-wmv',
            'wmx'	=> 'video/x-ms-wmx',
            'wvx'	=> 'video/x-ms-wvx',
            'movie'	=> 'video/x-sgi-movie',

            // adobe
            'pdf'	=> 'application/pdf',
            'psd'	=> 'image/vnd.adobe.photoshop',
            'ai'	=> 'application/postscript',
            'eps'	=> 'application/postscript',
            'ps'	=> 'application/postscript',

            // ms office
            'doc'	=> 'application/msword',
            'docx'	=> 'application/msword',
            'rtf'	=> 'application/rtf',
            'xls'	=> 'application/vnd.ms-excel',
            'ppt'	=> 'application/vnd.ms-powerpoint',

            // open office
            'odt'	=> 'application/vnd.oasis.opendocument.text',
            'ods'	=> 'application/vnd.oasis.opendocument.spreadsheet'

        );
    }

    /**
     * @param string $sFileName
     * @return string
     */
    public static function MimeContentType($sFileName)
    {
        $sResult = 'application/octet-stream';

        $aMimeTypes = self::GetMimeContentTypes();
        $sExt = strtolower(self::GetFileExtension($sFileName));
        if (!empty($sExt) && isset($aMimeTypes[$sExt])) {
            $sResult = $aMimeTypes[$sExt];
        }

        return $sResult;
    }

    /**
     * @param string $sMimeContentType
     * @return string
     */
    public static function GetFileExtensionFromMimeContentType($sMimeContentType)
    {
        $aMimeTypes = self::GetMimeContentTypes();
        return array_search($sMimeContentType, $aMimeTypes);
    }

    /**
     * @param string $sLanguage
     * @return string
     */
    public static function ConvertLanguageNameToShort($sLanguage)
    {
        $aList = array(
            'arabic' => 'ar',
            'bulgarian' => 'bg',
            'chinese-traditional' => 'zh-tw',
            'chinese-simplified' => 'zh-cn',
            'czech' => 'cs',
            'danish' => 'da',
            'dutch' => 'nl',
            'english' => 'en',
            'estonian' => 'et',
            'finnish' => 'fi',
            'french' => 'fr',
            'german' => 'de',
            'greek' => 'el',
            'hebrew' => 'he',
            'hungarian' => 'hu',
            'italian' => 'it',
            'japanese' => 'ja',
            'korean' => 'ko',
            'latvian' => 'lv',
            'lithuanian' => 'lt',
            'norwegian' => 'nb',
            'persian' => 'fa',
            'polish' => 'pl',
            'portuguese-brazil' => 'pt-br',
            'portuguese-portuguese' => 'pt',
            'romanian' => 'ro',
            'russian' => 'ru',
            'serbian' => 'sr',
            'slovenian' => 'sl',
            'spanish' => 'es',
            'swedish' => 'sv',
            'thai' => 'th',
            'turkish' => 'tr',
            'ukrainian' => 'uk',
            'vietnamese' => 'vi'
        );

        $sLanguage = strtolower($sLanguage);
        return isset($aList[$sLanguage]) ? $aList[$sLanguage] : 'en';
    }

    /**
     * @param int $iBigInt
     * @return int
     */
    public static function GetGoodBigInt($iBigInt)
    {
        if (null === $iBigInt || false == $iBigInt) {
            return 0;
        } elseif ($iBigInt > AU_API_PHP_INT_MAX) {
            return AU_API_PHP_INT_MAX;
        } elseif ($iBigInt < AU_API_PHP_INT_MIN) {
            return AU_API_PHP_INT_MIN;
        }

        return (int) $iBigInt;
    }

    /**
     * @param string $sUtf8String
     * @param int $iLength
     * @return string
     */
    public static function Utf8Truncate($sUtf8String, $iLength)
    {
        if (strlen($sUtf8String) <= $iLength) {
            return $sUtf8String;
        }

        while ($iLength >= 0) {
            if ((ord($sUtf8String[$iLength]) < 0x80) || (ord($sUtf8String[$iLength]) >= 0xC0)) {
                return substr($sUtf8String, 0, $iLength);
            }

            $iLength--;
        }

        return '';
    }

    /**
     * @param string $sUtf8String
     * @return string
     */
    public static function Utf8ToLowerCase($sUtf8String)
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($sUtf8String, 'utf-8');
        }

        $aMapping = array(
            "\xC3\x80" => "\xC3\xA0", "\xC3\x81" => "\xC3\xA1",
            "\xC3\x82" => "\xC3\xA2", "\xC3\x83" => "\xC3\xA3", "\xC3\x84" => "\xC3\xA4", "\xC3\x85" => "\xC3\xA5",
            "\xC3\x86" => "\xC3\xA6", "\xC3\x87" => "\xC3\xA7", "\xC3\x88" => "\xC3\xA8", "\xC3\x89" => "\xC3\xA9",
            "\xC3\x8A" => "\xC3\xAA", "\xC3\x8B" => "\xC3\xAB", "\xC3\x8C" => "\xC3\xAC", "\xC3\x8D" => "\xC3\xAD",
            "\xC3\x8E" => "\xC3\xAE", "\xC3\x8F" => "\xC3\xAF", "\xC3\x90" => "\xC3\xB0", "\xC3\x91" => "\xC3\xB1",
            "\xC3\x92" => "\xC3\xB2", "\xC3\x93" => "\xC3\xB3", "\xC3\x94" => "\xC3\xB4", "\xC3\x95" => "\xC3\xB5",
            "\xC3\x96" => "\xC3\xB6", "\xC3\x98" => "\xC3\xB8", "\xC3\x99" => "\xC3\xB9", "\xC3\x9A" => "\xC3\xBA",
            "\xC3\x9B" => "\xC3\xBB", "\xC3\x9C" => "\xC3\xBC", "\xC3\x9D" => "\xC3\xBD", "\xC3\x9E" => "\xC3\xBE",
            "\xC4\x80" => "\xC4\x81", "\xC4\x82" => "\xC4\x83", "\xC4\x84" => "\xC4\x85", "\xC4\x86" => "\xC4\x87",
            "\xC4\x88" => "\xC4\x89", "\xC4\x8A" => "\xC4\x8B", "\xC4\x8C" => "\xC4\x8D", "\xC4\x8E" => "\xC4\x8F",
            "\xC4\x90" => "\xC4\x91", "\xC4\x92" => "\xC4\x93", "\xC4\x96" => "\xC4\x97", "\xC4\x98" => "\xC4\x99",
            "\xC4\x9A" => "\xC4\x9B", "\xC4\x9C" => "\xC4\x9D", "\xC4\x9E" => "\xC4\x9F", "\xC4\xA0" => "\xC4\xA1",
            "\xC4\xA2" => "\xC4\xA3", "\xC4\xA4" => "\xC4\xA5", "\xC4\xA6" => "\xC4\xA7", "\xC4\xA8" => "\xC4\xA9",
            "\xC4\xAA" => "\xC4\xAB", "\xC4\xAE" => "\xC4\xAF", "\xC4\xB4" => "\xC4\xB5", "\xC4\xB6" => "\xC4\xB7",
            "\xC4\xB9" => "\xC4\xBA", "\xC4\xBB" => "\xC4\xBC", "\xC4\xBD" => "\xC4\xBE", "\xC5\x81" => "\xC5\x82",
            "\xC5\x83" => "\xC5\x84", "\xC5\x85" => "\xC5\x86", "\xC5\x87" => "\xC5\x88", "\xC5\x8A" => "\xC5\x8B",
            "\xC5\x8C" => "\xC5\x8D", "\xC5\x90" => "\xC5\x91", "\xC5\x94" => "\xC5\x95", "\xC5\x96" => "\xC5\x97",
            "\xC5\x98" => "\xC5\x99", "\xC5\x9A" => "\xC5\x9B", "\xC5\x9C" => "\xC5\x9D", "\xC5\x9E" => "\xC5\x9F",
            "\xC5\xA0" => "\xC5\xA1", "\xC5\xA2" => "\xC5\xA3", "\xC5\xA4" => "\xC5\xA5", "\xC5\xA6" => "\xC5\xA7",
            "\xC5\xA8" => "\xC5\xA9", "\xC5\xAA" => "\xC5\xAB", "\xC5\xAC" => "\xC5\xAD", "\xC5\xAE" => "\xC5\xAF",
            "\xC5\xB0" => "\xC5\xB1", "\xC5\xB2" => "\xC5\xB3", "\xC5\xB4" => "\xC5\xB5", "\xC5\xB6" => "\xC5\xB7",
            "\xC5\xB8" => "\xC3\xBF", "\xC5\xB9" => "\xC5\xBA", "\xC5\xBB" => "\xC5\xBC", "\xC5\xBD" => "\xC5\xBE",
            "\xC6\xA0" => "\xC6\xA1", "\xC6\xAF" => "\xC6\xB0", "\xC8\x98" => "\xC8\x99", "\xC8\x9A" => "\xC8\x9B",
            "\xCE\x86" => "\xCE\xAC", "\xCE\x88" => "\xCE\xAD", "\xCE\x89" => "\xCE\xAE", "\xCE\x8A" => "\xCE\xAF",
            "\xCE\x8C" => "\xCF\x8C", "\xCE\x8E" => "\xCF\x8D", "\xCE\x8F" => "\xCF\x8E", "\xCE\x91" => "\xCE\xB1",
            "\xCE\x92" => "\xCE\xB2", "\xCE\x93" => "\xCE\xB3", "\xCE\x94" => "\xCE\xB4", "\xCE\x95" => "\xCE\xB5",
            "\xCE\x96" => "\xCE\xB6", "\xCE\x97" => "\xCE\xB7", "\xCE\x98" => "\xCE\xB8", "\xCE\x99" => "\xCE\xB9",
            "\xCE\x9A" => "\xCE\xBA", "\xCE\x9B" => "\xCE\xBB", "\xCE\x9C" => "\xCE\xBC", "\xCE\x9D" => "\xCE\xBD",
            "\xCE\x9E" => "\xCE\xBE", "\xCE\x9F" => "\xCE\xBF", "\xCE\xA0" => "\xCF\x80", "\xCE\xA1" => "\xCF\x81",
            "\xCE\xA3" => "\xCF\x83", "\xCE\xA4" => "\xCF\x84", "\xCE\xA5" => "\xCF\x85", "\xCE\xA6" => "\xCF\x86",
            "\xCE\xA7" => "\xCF\x87", "\xCE\xA8" => "\xCF\x88", "\xCE\xA9" => "\xCF\x89", "\xCE\xAA" => "\xCF\x8A",
            "\xCE\xAB" => "\xCF\x8B", "\xD0\x81" => "\xD1\x91", "\xD0\x82" => "\xD1\x92", "\xD0\x83" => "\xD1\x93",
            "\xD0\x84" => "\xD1\x94", "\xD0\x85" => "\xD1\x95", "\xD0\x86" => "\xD1\x96", "\xD0\x87" => "\xD1\x97",
            "\xD0\x88" => "\xD1\x98", "\xD0\x89" => "\xD1\x99", "\xD0\x8A" => "\xD1\x9A", "\xD0\x8B" => "\xD1\x9B",
            "\xD0\x8C" => "\xD1\x9C", "\xD0\x8E" => "\xD1\x9E", "\xD0\x8F" => "\xD1\x9F", "\xD0\x90" => "\xD0\xB0",
            "\xD0\x91" => "\xD0\xB1", "\xD0\x92" => "\xD0\xB2", "\xD0\x93" => "\xD0\xB3", "\xD0\x94" => "\xD0\xB4",
            "\xD0\x95" => "\xD0\xB5", "\xD0\x96" => "\xD0\xB6", "\xD0\x97" => "\xD0\xB7", "\xD0\x98" => "\xD0\xB8",
            "\xD0\x99" => "\xD0\xB9", "\xD0\x9A" => "\xD0\xBA", "\xD0\x9B" => "\xD0\xBB", "\xD0\x9C" => "\xD0\xBC",
            "\xD0\x9D" => "\xD0\xBD", "\xD0\x9E" => "\xD0\xBE", "\xD0\x9F" => "\xD0\xBF", "\xD0\xA0" => "\xD1\x80",
            "\xD0\xA1" => "\xD1\x81", "\xD0\xA2" => "\xD1\x82", "\xD0\xA3" => "\xD1\x83", "\xD0\xA4" => "\xD1\x84",
            "\xD0\xA5" => "\xD1\x85", "\xD0\xA6" => "\xD1\x86", "\xD0\xA7" => "\xD1\x87", "\xD0\xA8" => "\xD1\x88",
            "\xD0\xA9" => "\xD1\x89", "\xD0\xAA" => "\xD1\x8A", "\xD0\xAB" => "\xD1\x8B", "\xD0\xAC" => "\xD1\x8C",
            "\xD0\xAD" => "\xD1\x8D", "\xD0\xAE" => "\xD1\x8E", "\xD0\xAF" => "\xD1\x8F", "\xD2\x90" => "\xD2\x91",
            "\xE1\xB8\x82" => "\xE1\xB8\x83", "\xE1\xB8\x8A" => "\xE1\xB8\x8B", "\xE1\xB8\x9E" => "\xE1\xB8\x9F", "\xE1\xB9\x80" => "\xE1\xB9\x81",
            "\xE1\xB9\x96" => "\xE1\xB9\x97", "\xE1\xB9\xA0" => "\xE1\xB9\xA1", "\xE1\xB9\xAA" => "\xE1\xB9\xAB", "\xE1\xBA\x80" => "\xE1\xBA\x81",
            "\xE1\xBA\x82" => "\xE1\xBA\x83", "\xE1\xBA\x84" => "\xE1\xBA\x85", "\xE1\xBB\xB2" => "\xE1\xBB\xB3"
        );

        return strtr(strtolower($sUtf8String), $aMapping);
    }

    /**
     * @param string $sPabUri
     * @return array
     */
    public static function LdapUriParse($sPabUri)
    {
        $aReturn = array(
            'host' => '127.0.0.1',
            'port' => 389,
            'search_dn' => '',
        );

        $sPabUriLower = strtolower($sPabUri);
        if ('ldap://' === substr($sPabUriLower, 0, 7)) {
            $sPabUriLower = substr($sPabUriLower, 7);
        }

        $aPabUriLowerExplode = explode('/', $sPabUriLower, 2);
        $aReturn['search_dn'] = isset($aPabUriLowerExplode[1]) ? $aPabUriLowerExplode[1] : '';

        if (isset($aPabUriLowerExplode[0])) {
            $aPabUriLowerHostPortExplode = explode(':', $aPabUriLowerExplode[0], 2);
            $aReturn['host'] = isset($aPabUriLowerHostPortExplode[0]) ? $aPabUriLowerHostPortExplode[0] : $aReturn['host'];
            $aReturn['port'] = isset($aPabUriLowerHostPortExplode[1]) ? (int) $aPabUriLowerHostPortExplode[1] : $aReturn['port'];
        }

        return $aReturn;
    }

    /**
     * @return string
     */
    public static function RequestUri()
    {
        $sUri = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $sUri = $_SERVER['REQUEST_URI'];
        } else {
            if (isset($_SERVER['SCRIPT_NAME'])) {
                if (isset($_SERVER['argv'], $_SERVER['argv'][0])) {
                    $sUri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
                } elseif (isset($_SERVER['QUERY_STRING'])) {
                    $sUri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
                } else {
                    $sUri = $_SERVER['SCRIPT_NAME'];
                }
            }
        }

        $sUri = '/' . ltrim($sUri, '/');
        return $sUri;
    }

    public static function DirMtime($dir)
    {
        $last_modified = 0;
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $modified = self::DirMtime($file);
            } else {
                $modified = filemtime($file);
            }
            if ($modified > $last_modified) {
                $last_modified = $modified;
            }
        }
        return $last_modified;
    }

    public static function GlobRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        $aPaternFiles = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);

        if (is_array($aPaternFiles) && is_array($files)) {
            foreach ($aPaternFiles as $dir) {
                $files = array_merge($files, self::GlobRecursive($dir . '/' . basename($pattern), $flags));
            }
        }

        return is_array($files) ? $files : array();
    }

    /**
     * @param string $sPhone
     * @return string
     */
    public static function ClearPhone($sPhone)
    {
        return preg_replace('/^0000000000/', '+', preg_replace(
            '/[^\d]+/',
            '',
            preg_replace('/^[+]/', '0000000000', trim($sPhone))
        ));
    }

    /**
     * @param string $sSearch
     * @return string
     */
    public static function ClearPhoneSearch($sSearch)
    {
        return preg_replace('/[\s\-()]+/', '', preg_replace('/^[+]/', '', trim($sSearch)));
    }

    /**
     * @param string $sSearch
     * @return bool
     */
    public static function IsPhoneSearch($sSearch)
    {
        return (bool) preg_match('/^[\d]{3,}$/', self::ClearPhoneSearch($sSearch));
    }

    /**
     * @param string $sMimeType
     * @param string $sFileName = ''
     * @return bool
     */
    public static function IsGDImageMimeTypeSuppoted($sMimeType, $sFileName = '')
    {
        $bResult = function_exists('gd_info');
        if ($bResult) {
            $bResult = false;
            switch (strtolower($sMimeType)) {
                case 'image/jpg':
                case 'image/jpeg':
                    $bResult = function_exists('imagecreatefromjpeg');
                    break;
                case 'image/png':
                    $bResult = function_exists('imagecreatefrompng');
                    break;
                case 'image/gif':
                    $bResult = function_exists('imagecreatefromgif');
                    break;
            }
        }

        return $bResult;
    }

    public static function GetDirectorySize($path)
    {
        $size = 0;
        $files = 0;
        $directories = 0;

        $handle = is_dir($path) ? opendir($path) : null;
        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                $nextpath = $path . '/' . $file;
                if ($file != '.' && $file != '..' && !is_link($nextpath)) {
                    if (is_dir($nextpath)) {
                        $directories++;
                        $result = self::GetDirectorySize($nextpath);
                        $size += $result['size'];
                        $files += $result['files'];
                        $directories += $result['directories'];
                    } elseif (is_file($nextpath)) {
                        $size += filesize($nextpath);
                        $files++;
                    }
                }
            }

            if (is_resource($handle)) {
                closedir($handle);
            }
        }

        return array(
          'size' => $size,
          'files' => $files,
          'directories' => $directories
        );
    }

    public static function SearchFiles($sPath, $sPattern)
    {
        $files = array();

        // Create recursive dir iterator which skips dot folders
        $oDirIterator = new \RecursiveDirectoryIterator(
            $sPath,
            \FilesystemIterator::SKIP_DOTS |
            \FilesystemIterator::UNIX_PATHS
        );

        $oIterators = new \RecursiveIteratorIterator(
            $oDirIterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        if ($sPattern === "*") {
            $sPattern = "\w+";
        } else {
            $sPattern = preg_quote($sPattern);
        }

        foreach ($oIterators as $oIterator) {
            $sName = $oIterator->getFilename();
            $aMatches = array();

            $iResult = preg_match("/" . $sPattern . "/ui", $sName, $aMatches);
            if ($sName !== '.sabredav' && $sName !== AU_API_HELPDESK_PUBLIC_NAME && $iResult === 1) {
                $files[] = $oIterator->getPathname();
            }
        }

        return $files;
    }

    public static function GetRemoteFileInfo($sUrl)
    {
        $aResult = array(
            'size' => 0,
            'content-type' => '',
            'code' => 0
        );

        $oCurl = \curl_init();
        \curl_setopt_array($oCurl, array(
            CURLOPT_URL => $sUrl,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true
        ));

        \curl_exec($oCurl);
        $aInfo = \curl_getinfo($oCurl);

        if ($aInfo) {
            $sContentType = '';
            $aResult['size'] = isset($aInfo['download_content_length']) ? (int) $aInfo['download_content_length'] : 0;

            if (isset($aInfo['content_type'])) {
                $aContentType = explode(';', $aInfo['content_type']);
                $sContentType = isset($aContentType[0]) ? $aContentType[0] : '';
            }

            $aResult['code'] = $aInfo['http_code'];
            $aResult['content-type'] = $sContentType;
        }

        if (\is_resource($oCurl)) {
            \curl_close($oCurl);
        }

        return $aResult;
    }

    /**
     * @param string $sUrl
     * @param int $iStep Default value = **1**
     * @return bool
     */
    public static function GetRemoteFileRealUrl($sUrl, $iStep = 1)
    {
        $oCurl = curl_init();
        \curl_setopt_array($oCurl, array(
            CURLOPT_URL => $sUrl,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false, //problems width soundcloud
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true
        ));
        curl_exec($oCurl);

        $aInfo = \curl_getinfo($oCurl);
        $iCode = \curl_getinfo($oCurl, CURLINFO_HTTP_CODE);

        curl_close($oCurl);

        if (($iCode === 301 || $iCode === 302) && isset($aInfo['redirect_url']) && $aInfo['redirect_url'] !== '' && $iStep < 2) {
            return self::GetRemoteFileRealUrl($aInfo['redirect_url'], ++$iStep);
        }

        return ($iCode === 200 || $iCode === 0 || $iCode === 400) ? $sUrl : false; //final result
    }

    public static function PopulateGoogleDriveFileInfo(&$oFileInfo)
    {
        if ($oFileInfo->mimeType !== "application/vnd.google-apps.folder" && !isset($oFileInfo->downloadUrl)) {
            switch($oFileInfo->mimeType) {
                case 'application/vnd.google-apps.document':
                    if (is_array($oFileInfo->exportLinks)) {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    } else {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks->{'application/vnd.openxmlformats-officedocument.wordprocessingml.document'};
                    }
                    $oFileInfo->title = $oFileInfo->title . '.docx';
                    break;
                case 'application/vnd.google-apps.spreadsheet':
                    if (is_array($oFileInfo->exportLinks)) {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    } else {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks->{'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'};
                    }
                    $oFileInfo->title = $oFileInfo->title . '.xlsx';
                    break;
                case 'application/vnd.google-apps.drawing':
                    if (is_array($oFileInfo->exportLinks)) {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks['image/png'];
                    } else {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks->{'image/png'};
                    }
                    $oFileInfo->title = $oFileInfo->title . '.png';
                    break;
                case 'application/vnd.google-apps.presentation':
                    if (is_array($oFileInfo->exportLinks)) {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks['application/vnd.openxmlformats-officedocument.presentationml.presentation'];
                    } else {
                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks->{'application/vnd.openxmlformats-officedocument.presentationml.presentation'};
                    }
                    $oFileInfo->title = $oFileInfo->title . '.pptx';
                    break;
                    /*				default:
                                        $oFileInfo->downloadUrl = $oFileInfo->exportLinks['application/pdf'];
                                        $oFileInfo->title = $oFileInfo->title . '.pdf';
                                        break;*/
            }
            /*
                        if (!isset($oFileInfo->fileSize))
                        {
                            $aRemoteFileInfo = \Aurora\System\Utils::GetRemoteFileInfo($oFileInfo->downloadUrl);
                            $oFileInfo->fileSize = $aRemoteFileInfo['size'];
                        }
             *
             */
        }
    }

    public static function GetAppUrl()
    {
        $aUrlParts = parse_url($_SERVER['HTTP_REFERER']);

        $sProtocol = !empty($aUrlParts['scheme']) ? $aUrlParts['scheme'] : 'http';
        $sHost = !empty($aUrlParts['host']) ? $aUrlParts['host'] : 'localhost';
        $sPath = !empty($aUrlParts['path']) ? str_replace('index.php', '', $aUrlParts['path']) : '';
        $sPort = !empty($aUrlParts['port']) ? ':' . $aUrlParts['port'] : '';

        return $sProtocol . '://' . $sHost . $sPath . $sPort;
    }

    /**
     * @param string $sValue
     * @return array
     */
    public static function ExplodeIntUids($sValue)
    {
        $aValue = explode(',', (string) $sValue);
        $aValue = array_map('trim', $aValue);
        $aValue = array_map('intval', $aValue);

        $aValue = array_filter($aValue, function ($iValue) {
            return 0 < $iValue;
        });

        return $aValue;
    }

    public static function parseIniString($sIniString)
    {
        $aResult = array();
        foreach (explode("\n", $sIniString) as $sLine) {
            $aValues = explode("=", $sLine, 2);
            if (isset($aValues[0], $aValues[1])) {
                $aResult[$aValues[0]] = trim(rtrim($aValues[1], "\r"), "\"");
            }
        }
        return $aResult;
    }

    /**
     * This function converst block CSS styles to inline styles in provided HTML.
     *
     * @param string $sHtml
     *
     * @return string
     */
    public static function ConvertCssToInlineStyles($sHtml)
    {
        $sResult = '';

        if (is_string($sHtml)) {
            preg_match_all('/<html.*?>/mi', $sHtml, $matches, PREG_SET_ORDER);
            if ($matches) {
                // custom styles processing for Outlook messages
                $sHtml = preg_replace('/<style><!--/mi', '<style>', $sHtml);
                $sHtml = preg_replace('/--><\/style>/mi', '</style>', $sHtml);

                // custom html processing for Outlook messages that removes empty outlook paragraphs
                $sHtml = preg_replace('/<o:p><\/o:p>/mi', '', $sHtml);

                $oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
                $sResult = $oCssToInlineStyles->convert($sHtml);
            } else {
                $sResult = $sHtml;
            }
        }

        return $sResult;
    }

    /**
     * @param bool $bDownload
     * @param string $sContentType
     * @param string $sFileName
     *
     * @return void
     */
    public static function OutputFileHeaders($bDownload, $sContentType, $sFileName)
    {
        if ($bDownload) {
            \header('Content-Type: ' . $sContentType, true);
        } else {
            $aParts = \explode('/', $sContentType, 2);
            if (\in_array(\strtolower($aParts[0]), array('image', 'video', 'audio')) ||
                \in_array(\strtolower($sContentType), array('application/pdf', 'application/x-pdf', 'text/html'))) {
                \header('Content-Type: ' . $sContentType, true);
            } elseif (\strtolower($sContentType) === 'application/octet-stream') {
                $sContentType = \MailSo\Base\Utils::MimeContentType($sFileName);
                \header('Content-Type: ' . $sContentType, true);
            } else {
                \header('Content-Type: text/plain', true);
            }
        }

        \header('Content-Disposition: ' . ($bDownload ? 'attachment' : 'inline') . '; ' .
            \trim(\MailSo\Base\Utils::EncodeHeaderUtf8AttributeValue('filename', $sFileName)), true);

        \header('Accept-Ranges: none', true);
        \header('Content-Transfer-Encoding: binary');
    }

    public static function OutputFileResource($iUserId, $sContentType, $sFileName, $rResource, $bThumbnail, $bDownload)
    {
        self::OutputFileHeaders($bDownload, $sContentType, $sFileName);

        if (!$bDownload && 'text/html' === $sContentType) {
            $sHtml = \stream_get_contents($rResource);
            if ($sHtml) {
                $sCharset = '';
                $aMacth = array();
                if (\preg_match('/charset[\s]?=[\s]?([^\s"\']+)/i', $sHtml, $aMacth) && !empty($aMacth[1])) {
                    $sCharset = $aMacth[1];
                }

                if ('' !== $sCharset && \MailSo\Base\Enumerations\Charset::UTF_8 !== $sCharset) {
                    $sHtml = \MailSo\Base\Utils::ConvertEncoding(
                        $sHtml,
                        \MailSo\Base\Utils::NormalizeCharset($sCharset, true),
                        \MailSo\Base\Enumerations\Charset::UTF_8
                    );
                }

                echo '<html><head></head><body>' .
                    \MailSo\Base\HtmlUtils::ClearHtmlSimple(self::ConvertCssToInlineStyles($sHtml), true) .
                    '</body></html>';
            }
        } else {
            if ($bThumbnail && !$bDownload) {
                Managers\Thumb::GetResource($iUserId, $rResource, $sFileName, true);
            } else {
                \MailSo\Base\Utils::FpassthruWithTimeLimitReset($rResource);
            }
        }
    }

    public static function GetClientFileResponse($sModule, $iUserId, $sFileName, $sTempName, $iSize)
    {
        $sMimeType = \MailSo\Base\Utils::MimeContentType($sFileName);
        $sModule = !empty($sModule) ? $sModule : 'System';

        $sHash = Api::EncodeKeyValues(array(
            'Module' => $sModule,
            'TempFile' => true,
            'UserId' => $iUserId,
            'Name' => $sFileName,
            'TempName' => $sTempName
        ));
        $aActions = array(
            'view' => array(
                'url' => '?file-cache/' . $sHash . '/view'
            ),
            'download' => array(
                'url' => '?file-cache/' . $sHash
            )
        );
        $oSettings = & Api::GetSettings();
        $iThumbnailLimit = ((int) $oSettings->GetValue('ThumbnailMaxFileSizeMb', 5)) * 1024 * 1024;
        $bThumb = ($oSettings->GetValue('AllowThumbnail', true) &&
                $iSize < $iThumbnailLimit && Utils::IsGDImageMimeTypeSuppoted($sMimeType, $sFileName));
        return array(
            'Name' => $sFileName,
            'FileName' => $sFileName,
            'TempName' => $sTempName,
            'MimeType' => $sMimeType,
            'Size' =>  (int) $iSize,
            'Hash' => $sHash,
            'Actions' => $aActions,
            'ThumbnailUrl' => $bThumb ? '?file-cache/' . $sHash . '/thumb' : '',
        );
    }

    /**
     * @param string $sFileName
     * @param string $sContentType
     * @param string $sMimeIndex = ''
     *
     * @return string
     */
    public static function clearFileName($sFileName, $sContentType, $sMimeIndex = '')
    {
        $sFileName = 0 === \strlen($sFileName) ? \preg_replace('/[^a-zA-Z0-9]/', '.', (empty($sMimeIndex) ? '' : $sMimeIndex . '.') . $sContentType) : $sFileName;
        $sClearedFileName = \preg_replace('/[\s]+/', ' ', \preg_replace('/[\.]+/', '.', $sFileName));
        $sExt = \MailSo\Base\Utils::GetFileExtension($sClearedFileName);

        $iSize = 100;
        if ($iSize < \strlen($sClearedFileName) - \strlen($sExt)) {
            $sClearedFileName = \substr($sClearedFileName, 0, $iSize) . (empty($sExt) ? '' : '.' . $sExt);
        }

        return \MailSo\Base\Utils::ClearFileName(\MailSo\Base\Utils::Utf8Clear($sClearedFileName));
    }

    public static function getShortClassName($sClassName)
    {
        if ($mPos = \strrpos($sClassName, '\\')) {
            return \substr($sClassName, $mPos + 1);
        }
        return $sClassName;
    }

    public static function getSanitizedFilename($sFileName)
    {
        return preg_replace("/[\/\*\?\[^\]<>\|:]/i", "", $sFileName);
    }

    public static function getClientIp()
    {
        $ipaddress = '';

        // The HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR headers can be spoofed and should not be trusted for security purposes.
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }

        return $ipaddress;
    }

    public static function getClientIpInsecure()
    {
        $ipaddress = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }

        return $ipaddress;
    }

    /**
     * Get size parameters from php.ini
     * @param string $sizeName
     * @return float size in bytes
     */
    public static function getSizeFromIni($sizeName)
    {
        $size = ini_get($sizeName);
        if ($size) {
            $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
            $size = (int) preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
            if ($unit) {
                // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
                return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
            } else {
                return round($size);
            }
        } else {
            return 0;
        }
    }
}

/**
 * @package Api
 */
class Ints
{
    /**
     * @return int
     */
    public static function getIntMax()
    {
        $iMax = 0x7fff;
        $iProbe = 0x7fffffff;
        while ($iMax == ($iProbe >> 16)) {
            $iMax = $iProbe;
            $iProbe = ($iProbe << 16) + 0xffff;
        }
        return $iMax;
    }
}

function fNullCallback() {}

defined('AU_API_PHP_INT_MAX') || define('AU_API_PHP_INT_MAX', (int) Ints::getIntMax());
defined('AU_API_PHP_INT_MIN') || define('AU_API_PHP_INT_MIN', (int) (AU_API_PHP_INT_MAX + 1));
