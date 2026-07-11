<?php

if(!defined('ABSPATH')){exit;}

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\NumberParseException;

class PhoneHelper {
    private static $phoneUtil = null;

    /**
     * Get the PhoneNumberUtil instance.
     */
    public static function getPhoneUtil() {
        if (self::$phoneUtil === null) {
            self::$phoneUtil = PhoneNumberUtil::getInstance();
        }
        return self::$phoneUtil;
    }

    /**
     * Parse a phone number with country auto-detection.
     *
     * @param string $phone
     * @param string $defaultRegion Default region (e.g. 'UA')
     * @return \libphonenumber\PhoneNumber|false
     */
    public static function parsePhone($phone, $defaultRegion = 'UA') {
        $phoneUtil = self::getPhoneUtil();

        try {
            $phone = trim($phone);

            $phoneNumber = $phoneUtil->parse($phone, $defaultRegion);

            // Fallback for Ukrainian numbers without a country code
            if (!$phoneUtil->isValidNumber($phoneNumber) && strpos($phone, '+') !== 0 && $defaultRegion === 'UA') {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                $cleanPhone = preg_replace('/^(380|38|80|8|0)/', '', $cleanPhone);
                $phone = '+380' . $cleanPhone;
                $phoneNumber = $phoneUtil->parse($phone, $defaultRegion);
            }

            return $phoneNumber;
        } catch (NumberParseException $e) {
            return false;
        }
    }
}

/**
 * Get the national significant number (without country code).
 *
 * @param string $phone
 * @param string $defaultRegion
 * @return string|false
 */
function short_phone_format($phone, $defaultRegion = 'UA') {
    if (empty($phone)) {
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    return PhoneHelper::getPhoneUtil()->getNationalSignificantNumber($phoneNumber);
}

/**
 * Format a number in E164 (+380501234567) — use for tel: links.
 *
 * @param string $phone
 * @param string $defaultRegion
 * @return string|false
 */
function fix_phone_format($phone, $defaultRegion = 'UA') {
    if (empty($phone)) {
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    return PhoneHelper::getPhoneUtil()->format($phoneNumber, PhoneNumberFormat::E164);
}

/**
 * Format a number for display.
 *
 * @param string $phone
 * @param string $defaultRegion
 * @param string $format 'international' or 'national'
 * @return string|false
 */
function nice_phone_format($phone, $defaultRegion = 'UA', $format = 'international') {
    if (empty($phone)) {
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    $phoneUtil = PhoneHelper::getPhoneUtil();

    if ($format === 'national') {
        return $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
    }

    return $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
}

/**
 * Validate a phone number.
 *
 * @param string $phone
 * @param string $defaultRegion
 * @param bool   $checkMobile Whether to require a mobile number
 * @return bool
 */
function check_phone($phone, $defaultRegion = 'UA', $checkMobile = false) {
    if (empty($phone)) {
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    $phoneUtil = PhoneHelper::getPhoneUtil();

    if (!$phoneUtil->isValidNumber($phoneNumber)) {
        return false;
    }

    if ($checkMobile) {
        $numberType = $phoneUtil->getNumberType($phoneNumber);
        return $numberType === PhoneNumberType::MOBILE ||
            $numberType === PhoneNumberType::FIXED_LINE_OR_MOBILE;
    }

    return true;
}

/**
 * Get structured information about a phone number.
 *
 * @param string $phone
 * @param string $defaultRegion
 * @return array|false
 */
function get_phone_info($phone, $defaultRegion = 'UA') {
    if (empty($phone)) {
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    $phoneUtil = PhoneHelper::getPhoneUtil();

    $info = [
        'valid' => $phoneUtil->isValidNumber($phoneNumber),
        'country_code' => $phoneNumber->getCountryCode(),
        'country' => $phoneUtil->getRegionCodeForNumber($phoneNumber),
        'national_number' => $phoneUtil->getNationalSignificantNumber($phoneNumber),
        'e164' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164),
        'international' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL),
        'national' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL),
        'type' => $phoneUtil->getNumberType($phoneNumber),
        'is_mobile' => in_array($phoneUtil->getNumberType($phoneNumber), [
            PhoneNumberType::MOBILE,
            PhoneNumberType::FIXED_LINE_OR_MOBILE
        ])
    ];

    try {
        if (class_exists('\libphonenumber\geocoding\PhoneNumberOfflineGeocoder')) {
            $geocoder = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();
            $info['location'] = $geocoder->getDescriptionForNumber($phoneNumber, 'uk');
        }
    } catch (\Exception $e) {
        $info['location'] = null;
    }

    try {
        if (class_exists('\libphonenumber\PhoneNumberToCarrierMapper')) {
            $carrier = \libphonenumber\PhoneNumberToCarrierMapper::getInstance();
            $info['carrier'] = $carrier->getNameForNumber($phoneNumber, 'uk');
        }
    } catch (\Exception $e) {
        $info['carrier'] = null;
    }

    return $info;
}

/**
 * Get an input mask for a number (or the default region example).
 *
 * @param string $phone
 * @param string $defaultRegion
 * @return string|false
 */
function get_phone_mask_format($phone, $defaultRegion = 'UA') {
    $phoneUtil = PhoneHelper::getPhoneUtil();

    if (empty($phone)) {
        $exampleNumber = $phoneUtil->getExampleNumberForType($defaultRegion, PhoneNumberType::MOBILE);

        if ($exampleNumber) {
            $formatted = $phoneUtil->format($exampleNumber, PhoneNumberFormat::INTERNATIONAL);
            return preg_replace('/[0-9]/', '_', $formatted);
        }
        return false;
    }

    $phoneNumber = PhoneHelper::parsePhone($phone, $defaultRegion);

    if ($phoneNumber === false) {
        return false;
    }

    $region = $phoneUtil->getRegionCodeForNumber($phoneNumber);
    $exampleNumber = $phoneUtil->getExampleNumberForType($region, PhoneNumberType::MOBILE);

    if ($exampleNumber) {
        $formatted = $phoneUtil->format($exampleNumber, PhoneNumberFormat::INTERNATIONAL);
        return preg_replace('/[0-9]/', '_', $formatted);
    }

    return false;
}
