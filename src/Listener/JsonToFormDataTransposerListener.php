<?php

namespace ThePlankmeister\FormDataTransposer\Listener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class JsonToFormDataTransposerListener
{
    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var bool
     */
    private $rethrowDecoderException;

    /**
     * @var bool
     */
    private $checkIsXmlHttpRequest;

    public function __construct(
        DecoderInterface $decoder,
        bool $rethrowDecoderException = true,
        bool $checkIsXmlHttpRequest = true
    ) {
        $this->decoder = $decoder;
        $this->rethrowDecoderException = $rethrowDecoderException;
        $this->checkIsXmlHttpRequest = $checkIsXmlHttpRequest;
    }

    /**
     * Conditionally fetches the JSON data from the request body, decodes it, and applies it to the request as if it
     * were POSTed form data, so that Form objects can use their handleRequest() method to process the form data.
     *
     * @see FormInterface::handleRequest()
     */
    public function transposeData(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $conditions = [
            (int) ('POST' === $request->getMethod()),
            (int) ('application/json' === $request->headers->get('Content-Type', '', false)[0]),
        ];
        if ($this->checkIsXmlHttpRequest) {
            $conditions[] = (int) ($request->isXmlHttpRequest());
        }

        if (count($conditions) !== array_sum($conditions)) {
            return;
        }

        $content = (string) $request->getContent(false);
        try {
            foreach ($this->decoder->decode($content, 'json') as $key => $value) {
                $request->request->set($key, $value);
            }
        } catch (UnexpectedValueException $e) {
            if ($this->rethrowDecoderException) {
                throw $e;
            }
        }
    }
}
