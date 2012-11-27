<?php

namespace spec\Painter;

use PHPSpec2\ObjectBehavior;

class Painter extends ObjectBehavior
{
    /**
     * @param \Imagine\Image\ImagineInterface $imagine
     * @param \Imagine\Image\ImageInterface   $image
     */
    function let($imagine, $image)
    {
        $imagine->load('')->willReturn($image);

        $this->beConstructedWith($imagine);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request   $request
     * @param \Symfony\Component\HttpFoundation\HeaderBag $headerBag
     * @param \Buzz\Message\Request                       $proxyRequest
     * @param \Buzz\Message\Response                      $proxyResponse
     */
    function it_should_not_process_too_big_boxes($request, $headerBag, $proxyRequest, $proxyResponse)
    {
        $proxyResponse->getStatusCode()->willReturn(200);
        $proxyResponse->getContent()->willReturn('');

        $headerBag->get('X-Painter-Box', ANY_ARGUMENT)->willReturn('100x100');
        $headerBag->get('X-Painter-Max-Box', ANY_ARGUMENT)->willReturn('10x10');

        $headerBag->get('X-Painter-Filter', ANY_ARGUMENT)->willReturn('thumbnail');

        $request->headers = $headerBag;

        $this
            ->shouldThrow('Painter\Exception\InvalidArgumentException', 'Too big box.')
            ->duringProcess($request, $proxyRequest, $proxyResponse);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request   $request
     * @param \Symfony\Component\HttpFoundation\HeaderBag $headerBag
     * @param \Buzz\Message\Request                       $proxyRequest
     * @param \Buzz\Message\Response                      $proxyResponse
     *
     * @param \Imagine\Image\ImagineInterface $imagine
     * @param \Imagine\Image\ImageInterface   $image
     */
    function it_should_process_thumbnails($request, $headerBag, $proxyRequest, $proxyResponse, $imagine, $image)
    {
        $proxyResponse->getStatusCode()->willReturn(200);
        $proxyResponse->getContent()->willReturn('');

        $headerBag->get('X-Painter-Box', ANY_ARGUMENT)->willReturn('100x100');
        $headerBag->get('X-Painter-Max-Box', ANY_ARGUMENT)->willReturn(null);

        $headerBag->get('X-Painter-Filter', ANY_ARGUMENT)->willReturn('thumbnail');

        $request->headers = $headerBag;

        $this->beConstructedWith($imagine);

        $imagine->load('')->willReturn($image);

        $image->thumbnail(ANY_ARGUMENTS)->willReturn($image);

        $this
            ->process($request, $proxyRequest, $proxyResponse)
            ->shouldHaveType('Symfony\Component\HttpFoundation\Response');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request   $request
     * @param \Symfony\Component\HttpFoundation\HeaderBag $headerBag
     * @param \Buzz\Message\Request                       $proxyRequest
     * @param \Buzz\Message\Response                      $proxyResponse
     *
     * @param \Imagine\Image\ImagineInterface $imagine
     * @param \Imagine\Image\ImageInterface   $image
     */
    function it_should_process_faces($request, $headerBag, $proxyRequest, $proxyResponse, $imagine, $image)
    {
        $proxyResponse->getStatusCode()->willReturn(200);
        $proxyResponse->getContent()->willReturn('');

        $headerBag->get('X-Painter-Box', ANY_ARGUMENT)->willReturn('100x100');
        $headerBag->get('X-Painter-Max-Box', ANY_ARGUMENT)->willReturn(null);

        $headerBag->get('X-Painter-Filter', ANY_ARGUMENT)->willReturn('face_square_thumbnail');

        $request->headers = $headerBag;

        $this->beConstructedWith($imagine);

        $imagine->load('')->willReturn($image);

        $image->crop(ANY_ARGUMENTS)->willReturn($image);
        $image->thumbnail(ANY_ARGUMENTS)->willReturn($image);

        $this
            ->process($request, $proxyRequest, $proxyResponse)
            ->shouldHaveType('Symfony\Component\HttpFoundation\Response');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request   $request
     * @param \Symfony\Component\HttpFoundation\HeaderBag $headerBag
     * @param \Buzz\Message\Request                       $proxyRequest
     * @param \Buzz\Message\Response                      $proxyResponse
     */
    function it_should_not_process_unknown_filters($request, $headerBag, $proxyRequest, $proxyResponse)
    {
        $proxyResponse->getStatusCode()->willReturn(200);
        $proxyResponse->getContent()->willReturn('');

        // Fucking willReturnArgument()...
        $headerBag->get('X-Painter-Box', ANY_ARGUMENT)->willReturn(null);
        $headerBag->get('X-Painter-Max-Box', ANY_ARGUMENT)->willReturn(null);

        $headerBag->get('X-Painter-Filter', ANY_ARGUMENT)->willReturn('some_unknown_filter');

        $request->headers = $headerBag;

        $this
            ->shouldThrow('Painter\Exception\InvalidArgumentException', 'Unknown filter.')
            ->duringProcess($request, $proxyRequest, $proxyResponse);
    }
}
