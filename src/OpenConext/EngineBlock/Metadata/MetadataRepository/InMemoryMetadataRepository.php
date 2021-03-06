<?php

namespace OpenConext\EngineBlock\Metadata\MetadataRepository;

use InvalidArgumentException;
use OpenConext\EngineBlock\Metadata\Entity\AbstractRole;
use OpenConext\EngineBlock\Metadata\Entity\IdentityProvider;
use OpenConext\EngineBlock\Metadata\Entity\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Class InMemoryMetadataRepository
 * @package OpenConext\EngineBlock\Metadata\MetadataRepository
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class InMemoryMetadataRepository extends AbstractMetadataRepository
{
    /**
     * @var ServiceProvider[]
     */
    private $serviceProviders = array();

    /**
     * @var IdentityProvider[]
     */
    private $identityProviders = array();

    /**
     * @param IdentityProvider[] $identityProviders
     * @param ServiceProvider[] $serviceProviders
     * @throws InvalidArgumentException
     */
    public function __construct(array $identityProviders, array $serviceProviders)
    {
        parent::__construct();

        foreach ($identityProviders as $identityProvider) {
            $this->registerIdentityProvider($identityProvider);
        }

        foreach ($serviceProviders as $serviceProvider) {
            $this->registerServiceProvider($serviceProvider);
        }
    }

    /**
     * @param ServiceProvider $serviceProvider
     * @return $this
     */
    public function registerServiceProvider(ServiceProvider $serviceProvider)
    {
        $this->serviceProviders[] = $serviceProvider;

        return $this;
    }

    /**
     * @param IdentityProvider $identityProvider
     * @return $this
     */
    public function registerIdentityProvider(IdentityProvider $identityProvider)
    {
        $this->identityProviders[] = $identityProvider;

        return $this;
    }

    /**
     * @param string $entityId
     * @return ServiceProvider|null
     */
    public function findIdentityProviderByEntityId($entityId)
    {
        $roles = $this->findIdentityProviders();

        foreach ($roles as $role) {
            if ($role->entityId === $entityId) {
                return $role;
            }
        }
    }

    /**
     * @param string $hash
     * @return string|null
     */
    public function findIdentityProviderEntityIdByMd5Hash($hash)
    {
        $roles = $this->findIdentityProviders();

        foreach ($roles as $role) {
            if (md5($role->entityId) === $hash) {
                return $role->entityId;
            }
        }
    }

    /**
     * @param $entityId
     * @param LoggerInterface|null $logger
     * @return null|ServiceProvider
     */
    public function findServiceProviderByEntityId($entityId, LoggerInterface $logger = null)
    {
        $roles = $this->findServiceProviders();

        foreach ($roles as $role) {
            if ($role->entityId === $entityId) {
                return $role;
            }
        }
    }

    /**
     * @return IdentityProvider[]
     */
    public function findIdentityProviders()
    {
        $identityProviders = $this->compositeFilter->filterRoles(
            $this->identityProviders
        );

        foreach ($identityProviders as $identityProvider) {
            $identityProvider->accept($this->compositeVisitor);
        }

        $indexedIdentityProviders = array();
        foreach ($identityProviders as $identityProvider) {
            $indexedIdentityProviders[$identityProvider->entityId] = $identityProvider;
        }
        return $indexedIdentityProviders;
    }

    /**
     * @return ServiceProvider[]
     */
    private function findServiceProviders()
    {
        $serviceProviders = $this->compositeFilter->filterRoles(
            $this->serviceProviders
        );

        foreach ($serviceProviders as $serviceProvider) {
            $serviceProvider->accept($this->compositeVisitor);
        }

        return $serviceProviders;
    }

    /**
     * @return AbstractRole[]
     */
    public function findEntitiesPublishableInEdugain()
    {
        /** @var AbstractRole[] $roles */
        $roles = array_merge($this->identityProviders, $this->serviceProviders);

        $publishableRoles = array();
        foreach ($roles as $role) {
            if (!$role->publishInEdugain) {
                continue;
            }

            $publishableRoles[] = $role;
        }

        $roles = $this->compositeFilter->filterRoles(
            $publishableRoles
        );

        foreach ($roles as $role) {
            $role->accept($this->compositeVisitor);
        }
        return $roles;
    }

    /**
     * @param array $scope
     * @return string[]
     */
    public function findAllIdentityProviderEntityIds(array $scope = [])
    {
        $identityProviders = $this->findIdentityProviders();

        $entityIds = array();
        foreach ($identityProviders as $identityProvider) {
            $entityIds[] = $identityProvider->entityId;
        }

        if (!empty($scope)) {
            $entityIds = array_intersect($entityIds, $scope);
        }

        return $entityIds;
    }

    /**
     * @return string[]
     */
    public function findReservedSchacHomeOrganizations()
    {
        $schacHomeOrganizations = array();

        $identityProviders = $this->findIdentityProviders();
        foreach ($identityProviders as $identityProvider) {
            if (!$identityProvider->schacHomeOrganization) {
                continue;
            }

            $schacHomeOrganizations[] = $identityProvider->schacHomeOrganization;
        }
        return $schacHomeOrganizations;
    }

    /**
     * @param array $identityProviderEntityIds
     * @return array|IdentityProvider[]
     * @throws EntityNotFoundException
     */
    public function findIdentityProvidersByEntityId(array $identityProviderEntityIds)
    {
        $identityProviders = $this->findIdentityProviders();

        $filteredIdentityProviders = array();
        foreach ($identityProviderEntityIds as $identityProviderEntityId) {
            if (!isset($identityProviders[$identityProviderEntityId])) {
                continue;
            }

            $filteredIdentityProviders[$identityProviderEntityId] = $identityProviders[$identityProviderEntityId];
        }
        return $filteredIdentityProviders;
    }
}
