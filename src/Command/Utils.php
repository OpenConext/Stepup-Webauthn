<?php

declare(strict_types=1);

namespace Surfnet\Webauthn\Command;

use DateTime;

class Utils
{
    static function base64url_decode($data, $strict = false)
    {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $b64 = strtr($data, '-_', '+/');

        // Decode Base64 string and return the original data
        return base64_decode($b64, $strict);
    }

    static function X509toString(array $parsedCert): string
    {
        $out = 'Subject: ';
        foreach ($parsedCert['subject'] as $key => $value) {
            $out .= $key . '=' . $value . "; ";
        }
        // Remove last semicolon
        $out = substr($out, 0, -2);
        $out .= "\nIssuer: ";
        foreach ($parsedCert['issuer'] as $key => $value) {
            $out .= $key . '=' . $value . "; ";
        }
        $out = substr($out, 0, -2);
        $out .= "\nSerial: " . $parsedCert['serialNumber'];
        $dateTime = (new DateTime())->setTimestamp($parsedCert['validFrom_time_t']);
        $out .= "\nValid from " . $dateTime->format('Y-m-d H:i:s');
        $dateTime->setTimestamp($parsedCert['validTo_time_t']);
        $out .= " to " . $dateTime->format('Y-m-d H:i:s');
        if ($dateTime->getTimestamp() < time()) {
            $out .= " (=- EXPIRED -=)";
        }
        return $out;
    }

    static function base64ToPEMCert(string $cert): string
    {
        $out = "-----BEGIN CERTIFICATE-----\n";
        $out .= chunk_split($cert, 64, "\n");
        $out .= "-----END CERTIFICATE-----\n";
        return $out;
    }


    static function recursivelyRemoveDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {

            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::recursivelyRemoveDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }
}