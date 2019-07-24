<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\ActionLog;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class ActionLogService
{
    const ACTOR_SYSTEM = 'system';
    const ACTOR_COMMAND = 'command';

    const ROLE_CUSTOMER = 'customer';
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'administrator';

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * @var EcommerceEntityManager
     */
    private $ecommerceEntityManager;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * ActionLogService constructor.
     */
    public function __construct(
        EcommerceEntityManager $ecommerceEntityManager,
        UserProviderInterface $userProvider
    )
    {
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->userProvider = $userProvider;
    }

    /**
     * @param string $brand
     * @param string $actionName
     * @param object $resource
     * @param string $actor
     * @param int $actorId
     * @param string $actorRole
     *
     * @throws Throwable
     */
    public function recordAction(
        string $brand,
        string $actionName,
        $resource,
        string $actor,
        int $actorId,
        string $actorRole
    )
    {
        $actionLog = $this->createActionLogEntity();

        $actionLog->setBrand($brand);
        $actionLog->setResourceName(get_class($resource));
        $actionLog->setResourceId($resource->getId());
        $actionLog->setActionName($actionName);
        $actionLog->setActor($actor);
        $actionLog->setActorId($actorId);
        $actionLog->setActorRole($actorRole);

        $this->saveActionLogEntity($actionLog);
    }

    /**
     * @param string $brand
     * @param string $actionName
     * @param object $resource
     *
     * @throws Throwable
     */
    public function recordUserAction(
        string $brand,
        string $actionName,
        $resource
    )
    {
        /** @var $currentUser User */
        $currentUser = $this->userProvider->getCurrentUser();

        $actionLog = $this->createActionLogEntity();

        $actionLog->setBrand($brand);
        $actionLog->setResourceName(get_class($resource));
        $actionLog->setResourceId($resource->getId());
        $actionLog->setActionName($actionName);
        $actionLog->setActor($currentUser->getEmail());
        $actionLog->setActorId($currentUser->getId());
        // $actionLog->setActorRole(); // todo - update

        $this->saveActionLogEntity($actionLog);
    }

    /**
     * @param string $brand
     * @param string $actionName
     * @param object $resource
     * @param Customer $customer
     *
     * @throws Throwable
     */
    public function recordCustomerAction(
        string $brand,
        string $actionName,
        $resource,
        Customer $customer
    )
    {
        $actionLog = $this->createActionLogEntity();

        $actionLog->setBrand($brand);
        $actionLog->setResourceName(get_class($resource));
        $actionLog->setResourceId($resource->getId());
        $actionLog->setActionName($actionName);
        $actionLog->setActor($customer->getEmail());
        $actionLog->setActorId($customer->getId());
        $actionLog->setActorRole(self::ROLE_CUSTOMER);

        $this->saveActionLogEntity($actionLog);
    }

    /**
     * @param string $brand
     * @param string $actionName
     * @param object $resource
     *
     * @throws Throwable
     */
    public function recordSystemAction(
        string $brand,
        string $actionName,
        $resource
    )
    {
        $actionLog = $this->createActionLogEntity();

        $actionLog->setBrand($brand);
        $actionLog->setResourceName(get_class($resource));
        $actionLog->setResourceId($resource->getId());
        $actionLog->setActionName($actionName);
        $actionLog->setActor(self::ACTOR_SYSTEM);

        $this->saveActionLogEntity($actionLog);
    }

    /**
     * @param string $brand
     * @param string $actionName
     * @param object $resource
     *
     * @throws Throwable
     */
    public function recordCommandAction(
        string $brand,
        string $actionName,
        $resource
    )
    {
        $actionLog = $this->createActionLogEntity();

        $actionLog->setBrand($brand);
        $actionLog->setResourceName(get_class($resource));
        $actionLog->setResourceId($resource->getId());
        $actionLog->setActionName($actionName);
        $actionLog->setActor(self::ACTOR_COMMAND);

        $this->saveActionLogEntity($actionLog);
    }

    protected function createActionLogEntity(): ActionLog
    {
        return new ActionLog();
    }

    protected function saveActionLogEntity(ActionLog $actionLog)
    {
        $this->ecommerceEntityManager->persist($actionLog);
        $this->ecommerceEntityManager->flush();
    }
}
