<?php

namespace spec\ThePlankmeister\FormDataTransposer\Listener;

use PhpCsFixer\Finder;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use ThePlankmeister\FormDataTransposer\Listener\JsonToFormDataTransposerListener;

class JsonToFormDataTransposerListenerSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @var Prophet
     */
    private $prophet;

    /**
     * @var ObjectProphecy
     */
    private $event;

    /**
     * @var ObjectProphecy
     */
    private $request;

    /**
     * @var ObjectProphecy
     */
    private $requestParams;

    public function let()
    {
        $this->prophet = new Prophet();
        $this->event = $this->prophet->prophesize(GetResponseEvent::class);
        $this->request = $this->prophet->prophesize(Request::class);
        $this->requestParams = $this->prophet->prophesize(ParameterBag::class);
        $this->request->request = $this->requestParams;
        $this->event->getRequest()->willReturn($this->request)->shouldBeCalledOnce();
        $decoder = new JsonDecode();
        $this->beConstructedWith($decoder);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(JsonToFormDataTransposerListener::class);
    }

    public function it_returns_early_when_not_post_request()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('GET')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(true)->shouldBeCalledOnce();

        $this->transposeData($this->event);
        $this->request->getContent()->shouldNotHaveBeenCalled();
        $this->requestParams->set()->shouldNotHaveBeenCalled();
    }

    public function it_returns_early_when_not_application_json_content_type()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/x-www-form-urlencoded'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(true)->shouldBeCalledOnce();

        $this->transposeData($this->event);
        $this->request->getContent()->shouldNotHaveBeenCalled();
        $this->requestParams->set()->shouldNotHaveBeenCalled();
    }

    public function it_returns_early_when_not_xml_http_request()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(false)->shouldBeCalledOnce();

        $this->transposeData($this->event);
        $this->request->getContent()->shouldNotHaveBeenCalled();
        $this->requestParams->set()->shouldNotHaveBeenCalled();
    }

    public function it_returns_early_when_not_post_and_should_not_check_xml_http(DecoderInterface $decoder)
    {
        $this->beConstructedWith($decoder, true, false);

        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('GET')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->shouldNotHaveBeenCalled();

        $this->transposeData($this->event);
        $this->request->getContent()->shouldNotHaveBeenCalled();
        $this->requestParams->set()->shouldNotHaveBeenCalled();
    }

    public function it_returns_early_when_not_application_json_and_should_not_check_xml_http()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/x-www-form-urlencoded'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->shouldNotHaveBeenCalled();

        $this->transposeData($this->event);
        $this->request->getContent()->shouldNotHaveBeenCalled();
        $this->requestParams->set()->shouldNotHaveBeenCalled();
    }

    public function it_rethrows_exception_when_invalid_json()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(true)->shouldBeCalledOnce();
        $this->request->getContent(false)->willReturn('{"invalidjson')->shouldBeCalledOnce();
        $this->requestParams->set()->shouldNotHaveBeenCalled();

        $this->shouldThrow(NotEncodableValueException::class)->during('transposeData', [$this->event]);
    }

    public function it_does_not_rethrow_exception_when_invalid_json()
    {
        $decoder = new JsonDecode();
        $this->beConstructedWith($decoder, false);

        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(true)->shouldBeCalledOnce();
        $this->request->getContent(false)->willReturn('{"invalidjson')->shouldBeCalledOnce();
        $this->requestParams->set()->shouldNotHaveBeenCalled();

        $this->transposeData($this->event);
    }

    public function it_correctly_transposes_values_from_json_to_request_params()
    {
        $headers = $this->prophet->prophesize(HeaderBag::class);
        $headers->get('Content-Type', '', false)->willReturn(['application/json'])->shouldBeCalledOnce();
        $this->request->headers = $headers;
        $this->request->getMethod()->willReturn('POST')->shouldBeCalledOnce();
        $this->request->isXmlHttpRequest()->willReturn(true)->shouldBeCalledOnce();
        $structure = [
            'colours' => ['red', 'green', 'blue'],
            'state' => 'done'
        ];
        $this->request->getContent(false)->willReturn(json_encode($structure))->shouldBeCalledOnce();
        $this->requestParams->set('colours', ['red', 'green', 'blue'])->shouldBeCalledOnce();
        $this->requestParams->set('state', 'done')->shouldBeCalledOnce();

        $this->transposeData($this->event);
    }
}
