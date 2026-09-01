<?php

namespace App\Domain\Customer;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Server-rendered QR codes for the receive journey (§128). The QR encodes a
 * canonical koriepay:// payload — the app scans it, resolves the KoriePay ID
 * and pre-fills the recipient. Rendering is server-side so the UI stays
 * honest and works offline (inline SVG, no external scripts).
 */
class QrService
{
    public function svg(string $payload, int $size = 240): string
    {
        return (new Builder(
            new SvgWriter(),
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 8,
        ))->build()->getString();
    }

    /** Inline <img> data URI (base64 SVG). */
    public function dataUri(string $payload, int $size = 240): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($payload, $size));
    }
}
