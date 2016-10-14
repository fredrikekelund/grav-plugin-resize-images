<?php
namespace Grav\Plugin;

require_once 'interface.php';

/**
 * Class GDAdapter
 * @package Grav\Plugin
 */
class GDAdapter implements ResizeAdapterInterface
{
    private $image;

    private $target;

    private $format;

    private $quality;

    private $original_width;

    private $original_height;

    /**
     * Initiates a new GDAdapter instance
     * @param  string $source - Source image path
     */
    public function __construct($source)
    {
        $size = getimagesize($source);
        $pathinfo = pathinfo($source);
        $extension = strtolower($pathinfo['extension']);

        $this->original_width = $size[0];
        $this->original_height = $size[1];

        if (preg_match('/jpe?g/', $extension)) {
            $this->image = imagecreatefromjpeg($source);
            $this->format = 'JPEG';
        } else if ($extension == 'png') {
            $this->image = imagecreatefrompng($source);
            $this->format = 'PNG';
        }

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
     * @return GDAdapter - Returns $this
     */
    public function resize($width, $height)
    {
        $this->target = imagecreatetruecolor($width, $height);
        $format = $this->getFormat();

        if ($format == 'PNG') {
            $transparent = imagecolorallocatealpha($this->target, 255, 255, 255, 127);

            imagealphablending($this->target, false);
            imagesavealpha($this->target, true);
            imagefilledrectangle($this->target, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($this->target, $this->image, 0, 0, 0, 0, $width, $height, $this->original_width, $this->original_height);

        return $this;
    }

    /**
     * Sets JPEG quality of target image
     * @param  int $quality
     * @return GDAdapter - Returns $this
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

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

        if ($format == 'JPEG') {
            $result = imagejpeg($this->target, $filename, $this->quality);
        } else if ($format == 'PNG') {
            $result = imagepng($this->target, $filename, 9);
        }

        imagedestroy($this->image);
        imagedestroy($this->target);

        return $result;
    }
}
