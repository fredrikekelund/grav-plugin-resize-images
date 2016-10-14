<?php
namespace Grav\Plugin;

require_once 'interface.php';

/**
 * Class ImagickAdapter
 * @package Grav\Plugin
 */
class ImagickAdapter implements ResizeAdapterInterface
{
    private $image;

    private $format;

    /**
     * Initiates a new ImagickAdapter instance
     * @param  string $source - Source image path
     */
    public function __construct($source)
    {
        $this->image = new \Imagick($source);
        $this->format = strtolower($this->image->getImageFormat());

        return $this;
    }

    /**
     * Gets the image format
     * @return string - Either 'JPEG' or 'PNG'
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Resizes the image to the specified dimensions
     * @param  float $width
     * @param  float $height
     * @return ImagickAdapter - Returns $this
     */
    public function resize($width, $height)
    {
        $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);

        return $this;
    }

    /**
     * Sets JPEG quality of target image
     * @param  int $quality
     * @return ImagickAdapter - Returns $this
     */
    public function setQuality($quality)
    {
        $this->image->setImageCompressionQuality($quality);

        return $this;
    }

    /**
     * Generates image and saves it to disk
     * @param  string $filename - Target filename for image
     * @return bool             - Returns true if successful, false otherwise
     */
    public function save($filename)
    {
        $format = $this->getFormat();

        if ($format == 'jpeg') {
            $this->image->setImageCompression(\Imagick::COMPRESSION_JPEG);
        } else if ($format == 'png') {
            $this->image->setImageCompression(\Imagick::COMPRESSION_ZIP);
        }

        $result = $this->image->writeImage($filename);
        $this->image->clear();

        return (bool) $result;
    }
}
