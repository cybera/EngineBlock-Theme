<?php

namespace OpenConext\EngineBlockFunctionalTestingBundle\Controllers;

use DOMDocument;
use OpenConext\EngineBlockFunctionalTestingBundle\Mock\EntityRegistry;
use OpenConext\EngineBlockFunctionalTestingBundle\Mock\MockServiceProvider;
use OpenConext\EngineBlockFunctionalTestingBundle\Saml2\AuthnRequestFactory;
use OpenConext\EngineBlockFunctionalTestingBundle\Saml2\Compat\Container;
use OpenConext\EngineBlockFunctionalTestingBundle\Service\EngineBlock;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\Response as SAMLResponse;
use SAML2\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ServiceProviderController
 * @package OpenConext\EngineBlockFunctionalTestingBundle\Controllers
 * @SuppressWarnings("PMD")
 */
class ServiceProviderController extends Controller
{
    /**
     * @var EntityRegistry
     */
    private $mockSpRegistry;

    /**
     * @var EngineBlock
     */
    private $engineBlock;

    public function __construct(EntityRegistry $spRegistry, EngineBlock $engineBlock)
    {
        $this->mockSpRegistry = $spRegistry;
        $this->engineBlock = $engineBlock;
    }

    /**
     * @param $spName
     * @return RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function triggerLoginRedirectAction($spName)
    {
        if (!$this->mockSpRegistry->has($spName)) {
            throw new BadRequestHttpException('No SP found for ' . $spName);
        }

        $factory = new AuthnRequestFactory();
        $authnRequest = $factory->createForRequestFromTo(
            $this->mockSpRegistry->get($spName),
            $this->engineBlock
        );

        $redirect = new HTTPRedirect();
        $url = $redirect->getRedirectURL($authnRequest);

        return new RedirectResponse($url);
    }

    /**
     * @param $spName
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function triggerLoginPostAction($spName)
    {
        if (!$this->mockSpRegistry->has($spName)) {
            throw new BadRequestHttpException('No SP found for ' . $spName);
        }

        $factory = new AuthnRequestFactory();
        $authnRequest = $factory->createForRequestFromTo(
            $this->mockSpRegistry->get($spName),
            $this->engineBlock
        );

        $redirect = new HTTPPost();
        $redirect->send($authnRequest);

        /** @var Container $container */
        $container = Utils::getContainer();
        return $container->getPostResponse();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \RuntimeException
     */
    public function assertionConsumerAction(Request $request)
    {
        try {
            $httpPostBinding = new HTTPPost();
            $message = $httpPostBinding->receive();
        } catch (\Exception $e1) {
            try {
                $httpRedirectBinding = new HTTPRedirect();
                $message = $httpRedirectBinding->receive();
            } catch (\Exception $e2) {
                throw new \RuntimeException(
                    'Unable to retrieve SAML message?',
                    1,
                    $e1
                );
            }
        }

        if (!$message instanceof SAMLResponse) {
            throw new \RuntimeException('Unrecognized message type received: ' . get_class($message));
        }

        $xml = base64_decode($request->get('SAMLResponse'));

        // Format the XML
        $doc = new DomDocument('1.0');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);
        $xml = $doc->saveXML();

        return new Response(
            $xml,
            200,
            ['Content-Type' => 'application/xml']
        );
    }

    /**
     * @param $spName
     * @return Response
     */
    public function metadataAction($spName)
    {
        /** @var MockServiceProvider $mockSp */
        $mockSp = $this->mockSpRegistry->get($spName);

        return new Response(
            $mockSp->getEntityDescriptor()->toXML()->ownerDocument->saveXML(),
            200,
            ['Content-Type' => 'application/xml']
        );
    }
}
