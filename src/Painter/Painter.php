<?php

namespace Painter;

use Imagine\Image\ImagineInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Buzz\Message\Request as BuzzRequest;
use Buzz\Message\Response as BuzzResponse;

use Painter\Exception\InvalidArgumentException;

/**
 * @author Alexey Shockov <alexey@shockov.com>
 */
class Painter
{
    /**
     * @var \Imagine\Image\ImagineInterface
     */
    private $imagine;

    /**
     * @param \Imagine\Image\ImagineInterface $imagine
     */
    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * @param string $box
     *
     * @return \Imagine\Image\Box
     */
    private static function createBox($box)
    {
        // TODO Validate...
        list($width, $height) = explode('x', $box);

        return new Box($width, $height);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Buzz\Message\Request                     $proxyRequest
     * @param \Buzz\Message\Response                    $proxyResponse
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function process(Request $request, BuzzRequest $proxyRequest, BuzzResponse $proxyResponse)
    {
        // TODO Check content type...
        $image          = $proxyResponse->getContent();

        $filter = $request->headers->get('X-Painter-Filter', null);

        if (!$filter) {
            return new Response($proxyResponse->getContent(), $proxyResponse->getStatusCode());
        }

        $processedImage = $this->imagine->load($image);

        $box    = $request->headers->get('X-Painter-Box', null);
        $maxBox = $request->headers->get('X-Painter-Max-Box', null);

        $params = array();

        if ($box) {
            $box           = self::createBox($box);
            $params['box'] = $box;

            if ($maxBox) {
                $maxBox = self::createBox($maxBox);

                if (!$maxBox->contains($box)) {
                    throw new InvalidArgumentException('Too big box.');
                }
            }
        }

        // TODO Refactor to magic :)
        if ('thumbnail' == $filter) {
            $processedImage = $this->thumbnail($processedImage, $params);
        } elseif ('face_square' == $filter) {
            $processedImage = $this->faceSquare($processedImage, $params);
        } elseif ('face_square_thumbnail' == $filter) {
            $processedImage = $this->faceSquareThumbnail($processedImage, $params);
        } else {
            throw new InvalidArgumentException('Unknown filter.');
        }

        // TODO Check Accept header (*/* or JPEG).

        // StreamedResponse set "Cache-Control: no-cache", fuck!
        return new Response($processedImage->get('jpeg'));
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array                         $params
     *
     * @return \Imagine\Image\ImageInterface
     */
    private function thumbnail(ImageInterface $image, $params = array())
    {
        if (!$params['box']) {
            throw new InvalidArgumentException('Unknown box.');
        }

        return $image->thumbnail($params['box'], ImageInterface::THUMBNAIL_OUTBOUND);
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     *
     * @return \Imagine\Image\ImageInterface
     */
    private function faceSquare(ImageInterface $image)
    {
        $currentBox = $image->getSize();

        if ($currentBox->getHeight() != $currentBox->getWidth()) {
            $point   = null;
            $cropBox = null;
            if ($currentBox->getHeight() > $currentBox->getWidth()) {
                // Portrait.
                $point   = new Point(0, 0);
            } else {
                $point   = new Point(round(($currentBox->getWidth() - $currentBox->getHeight()) / 2), 0);
            }

            $cropBox = new Box($currentBox->getWidth(), $currentBox->getWidth());

            $image = $image->crop($point, $cropBox);
        }

        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array                         $params
     *
     * @return \Imagine\Image\ImageInterface
     */
    private function faceSquareThumbnail(ImageInterface $image, $params = array())
    {
        return $this->thumbnail($this->faceSquare($image), $params);
    }
}
