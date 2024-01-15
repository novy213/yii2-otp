<?php
/**
 * Author: Semen Dubina
 * Date: 19.01.16
 * Time: 15:24
 */

namespace novy213\otp;

use ParagonIE\ConstantTime\Base32;
use novy213\otp\helpers\OtpHelper;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\validators\UrlValidator;

/**
 * Class Collection is a single otp module with initialization and code-validation
 *
 * Example application configuration:
 *
 * ~~~
 *  'components' => [
 *      'otp' => [
 *          'class' => 'novy213\otp\Otp',
 *          'algorithm' => novy213\otp\Otp::ALGORITHM_TOTP
 *          'digits' => 6,
 *          'digest' => 'sha256',
 *          'issuer' => 'sam002',
 *          'label' => 'yii2-otp',
 *          'imgLabelUrl' => Yii,
 *          'secretLength' => 16
 *     ]
 *     ...
 * ]
 * ~~~
 *
 * @author Semen Dubina <sam@sam002.net>
 * @package novy213\otp
 */
class Otp extends Component
{

    const ALGORITHM_TOTP = 'totp';
    const ALGORITHM_HOTP = 'hotp';

    const SECRET_LENGTH_MIN = 8;
    const SECRET_LENGTH_MAX = 1024;

    /**
     * @var string
     */
    public $algorithm = self::ALGORITHM_HOTP;

    /**
     * @var int
     */
    public $digits = 6;

    /**
     * @var string
     */
    public $digest = 'sha256';

    /**
     * @var int
     */
    public $interval = 30;

    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @var string
     */
    public $issuer = '';

    /**
     * @var string
     */
    public $label = 'yii2-otp';

    /**
     * @var null
     */
    public $imgLabelUrl = null;

    /**
     * @var int
     */
    public $secretLength = 64;

    private $secret = null;


    /**
     * @var \OTPHP\OTP
     */
    private $otp = null;

    public function init()
    {
        parent::init();
        if ($this->algorithm === self::ALGORITHM_TOTP) {
            $this->otp = OtpHelper::getTotp($this->label, $this->digits, $this->digest, $this->interval, $this->issuer);
        } elseif ($this->algorithm === self::ALGORITHM_HOTP) {
            $this->otp = OtpHelper::getHotp($this->label, $this->digits, $this->digest, $this->counter, $this->issuer);
        } else {
            throw new InvalidConfigException('otp::$algorithm = \"' . $this->algorithm . '\" not allowed, only Otp::ALGORITHM_TOTP or Otp::ALGORITHM_HOTP');
        }

        if (!empty($this->imgLabelUrl) && is_string($this->imgLabelUrl)) {
            $validator = new UrlValidator();
            if ($validator->validate($this->imgLabelUrl)) {
                $this->otp->setParameter('image',$this->imgLabelUrl);
            } else {
                throw new InvalidConfigException($validator->message);
            }
        }
    }

    /**
     * @return \OTPHP\OTP
     */
    public function getOtp()
    {
        $this->otp->setParameter('secret', strtoupper($this->getSecret()));
        return $this->otp;
    }

    /**
     * @return null|string
     * @throws InvalidConfigException
     */
    public function getSecret()
    {
        if (!is_numeric($this->secretLength) || $this->secretLength < self::SECRET_LENGTH_MIN || $this->secretLength > self::SECRET_LENGTH_MAX) {
            throw new InvalidConfigException('otp::$length only integer, min='. self::SECRET_LENGTH_MIN .'and max=' . self::SECRET_LENGTH_MAX);
        }
        if (empty($this->secret)) {
            $this->secret = OtpHelper::generateSecret($this->secretLength);
        }
        return $this->secret;
    }

    public function setSecret($value)
    {
        if(strlen($value) !== $this->secretLength) {
            throw new InvalidConfigException('Otp::setSecret length is not equal to ' . $this->secretLength . ' ([\'length\'] component settenings)');
        } elseif ( strlen(Base32::decodeUpper(strtoupper($value))) < 1 ) {
            throw new InvalidConfigException('Otp::setSecret incorect, encode as Base32');
        }
        $this->otp->setParameter('secret', strtoupper($value));
        $this->secret = $value;
    }

    public function valideteCode($code, $window = null)
    {
        if ($this->counter === 0 && $this->algorithm === self::ALGORITHM_TOTP) {
            //todo add time configuration
            $this->counter = null;
        }
        return $this->otp->verify($code, $this->counter, $window);
    }
}
