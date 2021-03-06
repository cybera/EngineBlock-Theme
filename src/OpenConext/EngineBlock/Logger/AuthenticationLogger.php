<?php

namespace OpenConext\EngineBlock\Logger;

use DateTime;
use OpenConext\EngineBlock\Authentication\Value\CollabPersonId;
use OpenConext\EngineBlock\Authentication\Value\KeyId;
use OpenConext\Value\Saml\Entity;
use Psr\Log\LoggerInterface;

class AuthenticationLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * KeyId is nullable in order to be able to differentiate between asking no specific key,
     * the default key KeyId('default') and a specific key.
     *
     * @param Entity         $serviceProvider
     * @param Entity         $identityProvider
     * @param CollabPersonId $collabPersonId
     * @param array          $proxiedServiceProviders
     * @param string         $workflowState
     * @param KeyId|null     $keyId
     */
    public function logGrantedLogin(
        Entity $serviceProvider,
        Entity $identityProvider,
        CollabPersonId $collabPersonId,
        array $proxiedServiceProviders,
        $workflowState,
        KeyId $keyId = null
    ) {
        $proxiedServiceProviderEntityIds = array_map(
            function (Entity $entity) {
                return $entity->getEntityId()->getEntityId();
            },
            $proxiedServiceProviders
        );

        $timestamp = $this->generateTimestamp();

        $this->logger->info(
            'login granted',
            [
                'login_stamp'           => $timestamp,
                'user_id'               => $collabPersonId->getCollabPersonId(),
                'sp_entity_id'          => $serviceProvider->getEntityId()->getEntityId(),
                'idp_entity_id'         => $identityProvider->getEntityId()->getEntityId(),
                'key_id'                => $keyId ? $keyId->getKeyId() : null,
                'proxied_sp_entity_ids' => $proxiedServiceProviderEntityIds,
                'workflow_state'        => $workflowState
            ]
        );
    }

    /**
     * Generates a timestamp that is equal to the RFC3339_EXTENDED format
     *
     * This format is introduced in PHP7, as PHP5 does not support this kind of precision.
     * This method fakes the PHP7 behaviour by adding the microseconds manually.
     *
     * One day when the PHP5 dependency is lost, we can simply use RFC3339_EXTENDED
     *
     * @return string
     */
    private function generateTimestamp()
    {
        $microTime = microtime(true);
        $microseconds = sprintf("%06d", ($microTime - floor($microTime)) * 1000000);
        $timestamp = new DateTime(date('Y-m-d H:i:s.' . $microseconds, $microTime));
        return $timestamp->format('Y-m-d\TH:i:s.uP');
    }
}
