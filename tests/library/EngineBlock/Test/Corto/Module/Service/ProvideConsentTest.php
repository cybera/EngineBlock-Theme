<?php

use OpenConext\EngineBlock\Metadata\ConsentSettings;
use OpenConext\EngineBlock\Metadata\Entity\IdentityProvider;
use OpenConext\EngineBlock\Metadata\Entity\ServiceProvider;
use OpenConext\EngineBlock\Metadata\MetadataRepository\InMemoryMetadataRepository;
use OpenConext\EngineBlock\Service\ConsentServiceInterface;
use SAML2\Assertion;
use SAML2\AuthnRequest;
use SAML2\Response;

class EngineBlock_Test_Corto_Module_Service_ProvideConsentTest extends PHPUnit_Framework_TestCase
{
    /** @var EngineBlock_Corto_XmlToArray */
    private $xmlConverterMock;

    /** @var EngineBlock_Corto_Model_Consent_Factory */
    private $consentFactoryMock;

    /** @var EngineBlock_Corto_Model_Consent */
    private $consentMock;

    /** @var ConsentServiceInterface */
    private $consentService;

    /** @var EngineBlock_Corto_ProxyServer */
    private $proxyServerMock;

    /** @var Twig_Environment */
    private $twig;

    public function setup() {
        $diContainer              = EngineBlock_ApplicationSingleton::getInstance()->getDiContainer();

        $this->proxyServerMock    = $this->mockProxyServer();
        $this->xmlConverterMock   = $this->mockXmlConverter($diContainer->getXmlConverter());
        $this->consentFactoryMock = $diContainer->getConsentFactory();
        $this->consentMock        = $this->mockConsent();
        $this->consentService     = $this->mockConsentService();
        $this->twig               = $this->mockTwig();
    }

    public function testConsentRequested()
    {
        $provideConsentService = $this->factoryService();

        $provideConsentService->serve(null);
    }

    public function testConsentIsSkippedWhenPriorConsentIsStored()
    {
        $provideConsentService = $this->factoryService();

        Phake::when($this->consentMock)
            ->explicitConsentWasGivenFor(Phake::anyParameters())
            ->thenReturn(true);

        $provideConsentService->serve(null);

        Phake::verify($this->proxyServerMock->getBindingsModule())
            ->send(Phake::capture($message), Phake::capture($metadata));
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:consent:prior', $message->getConsent());
    }

    public function testConsentIsSkippedWhenGloballyDisabled()
    {
        $this->proxyServerMock->getRepository()->fetchServiceProviderByEntityId('testSp')->isConsentRequired = false;

        $provideConsentService = $this->factoryService();

        $provideConsentService->serve(null);

        Phake::verify($this->proxyServerMock->getBindingsModule())
            ->send(Phake::capture($message), Phake::capture($metadata));
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:consent:inapplicable', $message->getConsent());
    }

    public function testConsentIsSkippedWhenDisabledPerSp()
    {
        $idp = $this->proxyServerMock->getRepository()->fetchIdentityProviderByEntityId('testIdP');

        $idp->setConsentSettings(
            new ConsentSettings([
                [
                    'name' => 'testSp',
                    'type' => 'no_consent',
                ]
            ])
        );

        $provideConsentService = $this->factoryService();

        $provideConsentService->serve(null);

        Phake::verify($this->proxyServerMock->getBindingsModule())
            ->send(Phake::capture($message), Phake::capture($metadata));
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:consent:inapplicable', $message->getConsent());
    }

    /**
     * @return EngineBlock_Corto_ProxyServer
     */
    private function mockProxyServer()
    {
        // Mock twig, a dependency of proxy server
        $twigMock = Phake::mock(Twig_Environment::class);
        // Mock proxy server
        /** @var EngineBlock_Corto_ProxyServer $proxyServerMock */
        $proxyServerMock = Phake::partialMock('EngineBlock_Corto_ProxyServer', $twigMock);
        $proxyServerMock->setHostname('test-host');

        $proxyServerMock->setRepository(new InMemoryMetadataRepository(
            array(new IdentityProvider('testIdP')),
            array(new ServiceProvider('testSp'))
        ));

        $bindingsModuleMock = $this->mockBindingsModule();
        $proxyServerMock->setBindingsModule($bindingsModuleMock);

        Phake::when($proxyServerMock)
            ->renderTemplate(Phake::anyParameters())
            ->thenReturn(null);

        Phake::when($proxyServerMock)
            ->sendOutput(Phake::anyParameters())
            ->thenReturn(null);

        return $proxyServerMock;
    }

    /**
     * @return EngineBlock_Corto_Module_Bindings
     */
    private function mockBindingsModule()
    {
        $spRequest = new AuthnRequest();
        $spRequest->setId('SPREQUEST');
        $spRequest->setIssuer('testSp');
        $spRequest = new EngineBlock_Saml2_AuthnRequestAnnotationDecorator($spRequest);

        $ebRequest = new AuthnRequest();
        $ebRequest->setId('EBREQUEST');
        $ebRequest = new EngineBlock_Saml2_AuthnRequestAnnotationDecorator($ebRequest);

        $dummyLog = new Psr\Log\NullLogger();
        $authnRequestRepository = new EngineBlock_Saml2_AuthnRequestSessionRepository($dummyLog);
        $authnRequestRepository->store($spRequest);
        $authnRequestRepository->store($ebRequest);
        $authnRequestRepository->link($ebRequest, $spRequest);

        $assertion = new Assertion();
        $assertion->setAttributes(array(
            'urn:org:openconext:corto:internal:sp-entity-id' => array(
                'testSp'
            ),
            'urn:mace:dir:attribute-def:cn' => array(
                null
            )
        ));
        $assertion->setNameId(array(
            'Value' => 'nameid',
        ));

        $responseFixture = new Response();
        $responseFixture->setInResponseTo('EBREQUEST');
        $responseFixture->setAssertions(array($assertion));
        $responseFixture = new EngineBlock_Saml2_ResponseAnnotationDecorator($responseFixture);
        $responseFixture->setOriginalIssuer('testIdP');

        // Mock bindings module
        /** @var EngineBlock_Corto_Module_Bindings $bindingsModuleMock */
        $bindingsModuleMock = Phake::mock('EngineBlock_Corto_Module_Bindings');
        Phake::when($bindingsModuleMock)
            ->receiveResponse()
            ->thenReturn($responseFixture);

        return $bindingsModuleMock;
    }

    /**
     * @param EngineBlock_Corto_XmlToArray $xmlConverterMock
     * @return EngineBlock_Corto_XmlToArray
     */
    private function mockXmlConverter(EngineBlock_Corto_XmlToArray $xmlConverterMock)
    {
        // Mock xml conversion
        $xmlFixture = array(
            'urn:org:openconext:corto:internal:sp-entity-id' => array(
                'testSp'
            ),
            'urn:mace:dir:attribute-def:cn' => array(
                null
            )
        );
        Phake::when($xmlConverterMock)
            ->attributesToArray(Phake::anyParameters())
            ->thenReturn($xmlFixture);

        return $xmlConverterMock;
    }

    /**
     * @param EngineBlock_Corto_Model_Consent_Factory $this->consentFactoryMock
     * @return EngineBlock_Corto_Model_Consent
     */
    private function mockConsent()
    {
        $consentMock = Phake::mock('EngineBlock_Corto_Model_Consent');
        Phake::when($consentMock)
            ->explicitConsentWasGivenFor(Phake::anyParameters())
            ->thenReturn(false);
        Phake::when($this->consentFactoryMock)
            ->create(Phake::anyParameters())
            ->thenReturn($consentMock);

        return $consentMock;
    }

    /**
     * @return ConsentService
     */
    private function mockConsentService()
    {
        $mock = Phake::mock(ConsentServiceInterface::class);
        Phake::when($mock)
            ->countAllFor(Phake::anyParameters())
            ->thenReturn(3);

        return $mock;
    }


    private function mockTwig()
    {
        $mock = Phake::mock(\Twig\Environment::class);
        Phake::when($mock)
            ->render(Phake::anyParameters())
            ->thenReturn('');

        return $mock;
    }

    /**
     * @return EngineBlock_Corto_Module_Service_ProvideConsent
     */
    private function factoryService()
    {
        return new EngineBlock_Corto_Module_Service_ProvideConsent(
            $this->proxyServerMock,
            $this->xmlConverterMock,
            $this->consentFactoryMock,
            $this->consentService,
            $this->twig
        );
    }
}
