<?php
use Endroid\QrCode\QrCode;

class ExtractorQR {
    /**
     * Create QR
     * Creates a QR code with the given text and returns the data URI.
     *
     * @param string $data Text to encode.
     *
     * @return string
     */
    public static function createQR($data) {
        $qrCode = new QrCode();
        $qrCode->setText($data)
            ->setSize(450)
            ->setPadding(0)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255))
            ->setImageType(QrCode::IMAGE_TYPE_PNG);

        return $qrCode->getDataUri();
    }
}