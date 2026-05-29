<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class EpcQrService
{
    /**
     * Generate an EPC QR code as base64 PNG data URI.
     *
     * @param string $iban     IBAN (spaces allowed, will be stripped)
     * @param string $name     Account holder name (max 70 chars)
     * @param float  $amount   Amount in EUR
     * @param string $reference Unstructured reference (max 140 chars)
     * @param string $bic      BIC/SWIFT code (optional)
     * @return string          data:image/png;base64,... URI
     */
    public static function generate(string $iban, string $name, float $amount, string $reference, string $bic = ''): string
    {
        $iban = str_replace(' ', '', strtoupper($iban));
        $name = mb_substr(trim($name), 0, 70);
        $reference = mb_substr(trim($reference), 0, 140);
        $bic = strtoupper(trim($bic));

        // EPC069-12 QR code format
        $lines = [
            'BCD',                              // Service Tag
            '002',                              // Version
            '1',                                // Character set (UTF-8)
            'SCT',                              // Identification code
            $bic,                               // BIC (optional)
            $name,                              // Beneficiary name
            $iban,                              // IBAN
            'EUR' . number_format($amount, 2, '.', ''), // Amount
            '',                                 // Purpose (empty)
            '',                                 // Structured reference (empty)
            $reference,                         // Unstructured reference
            '',                                 // Information to beneficiary
        ];

        $data = implode("\n", $lines);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'imageBase64' => true,
            'scale' => 8,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Generate an EPC QR code as raw PNG binary (for email attachment).
     */
    public static function generatePng(string $iban, string $name, float $amount, string $reference, string $bic = ''): string
    {
        $dataUri = self::generate($iban, $name, $amount, $reference, $bic);
        // Strip "data:image/png;base64," prefix
        $base64 = preg_replace('/^data:image\/png;base64,/', '', $dataUri);
        return base64_decode($base64);
    }
}
