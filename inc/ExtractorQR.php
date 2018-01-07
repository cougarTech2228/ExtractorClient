<?php

use Endroid\QrCode\QrCode;

class ExtractorQR
{
    /**
     * Create QR Object
     * Creates a QR code with the given text and returns the QR object.
     *
     * @param string $text Text to encode.
     *
     * @return QrCode
     */
    public static function create($text)
    {
        $qrCode = new QrCode();
        try {
            $qrCode->setText($text)
                   ->setSize(300)
                   ->setPadding(0)
                   ->setErrorCorrection('high')
                   ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
                   ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
                   ->setImageType(QrCode::IMAGE_TYPE_PNG);

        } catch (\Endroid\QrCode\Exceptions\ImageTypeInvalidException $e) {
            die($e->getMessage());
        }

        return $qrCode;
    }

    /**
     * Create URI
     * Create QR code and return data URI.
     *
     * @param string $text Text to encode.
     *
     * @return string
     */
    public static function uri($text)
    {
        $qr = self::create($text);

        return $qr->getDataUri();
    }

    /**
     * Create Start QR
     * Creates the starting QR code.
     *
     * @param int $num Number of QR to display.
     *
     * @return string
     */
    public static function start($num)
    {
        $qr = self::create('START:' . $num);

        return $qr->getDataUri();
    }
}
