<?php
/**
 * Author: Semen Dubina
 * Date: 03.02.16
 * Time: 23:06
 */

namespace novy213\otp\helpers;

use ParagonIE\ConstantTime\Base32;
use yii\base\Security;
use OTPHP\HOTP;
use OTPHP\TOTP;

class OtpHelper
{

    /**
     * @param int $length
     * @return string
     */
    public static function generateSecret($length = 20)
    {
        $security = new Security();
        $full = Base32::encode($security->generateRandomString($length));
        return substr($full, 0, $length);
    }

    /**
     * @param string $label
     * @param int $digits
     * @param string $digest
     * @param int $interval
     * @param string $issuer
     * @return TOTP
     */
    public function getTotp($label = '', $digits = 6, $digest = 'sha1', $interval = 30, $issuer='')
    {
        $totp = TOTP::create($this->generateSecret(), 30, $digest, $digits, 30);
        if(!empty($issuer)) {
            $totp->setIssuer($issuer);
        }

        return $totp;
    }

    /**
     * @param string $label
     * @param int $digits
     * @param string $digest
     * @param int $counter
     * @param string $issuer
     * @return HOTP
     */
    public function getHotp($label = '', $digits = 6, $digest = 'sha1', $counter = 0, $issuer='')
    {
        $hotp = HOTP::create($this->generateSecret(), $counter, $digest, $digits);
        if(!empty($issuer)) {
            $hotp->setIssuer($issuer);
        }

        return $hotp;
    }
}
