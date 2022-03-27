<?php

namespace Railroad\Ecommerce\Services;

use DateTimeInterface;
use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\UserProduct;

class UserProductAccessService
{
    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * This stores a cache of user_id => [users products] so we don't need to query the database multiple times
     * per user id.
     *
     * @var array
     */
    private $userProductCache = [];

    public function __construct(UserProductService $userProductService)
    {
        $this->userProductService = $userProductService;
    }

    /**
     * @param $userId
     * @return UserProduct[]
     * @throws ORMException
     */
    public function getUsersProducts($userId)
    {
        if (empty($userId)) {
            return [];
        }

        if (isset($this->userProductCache[$userId])) {
            return $this->userProductCache[$userId];
        }

        $this->userProductCache[$userId] = $this->userProductService->getAllUsersProducts($userId);

        return $this->userProductCache[$userId];
    }

    /**
     * In legacy terminology this returns whether the user is a 'member'. This will return true for any membership
     * access, even lifetime. Even if the users' subscription is cancelled or stopped,
     * but they still have some membership time left, this will return true.
     *
     * @param $userId
     * @return bool
     * @throws ORMException
     */
    public function hasAllContentAccess($userId)
    {
        foreach ($this->getUsersProducts($userId) as $userProduct) {
            if ($userProduct->isValid() && $userProduct->getProduct()->isAllContentAccessProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Specific content access is new terminology for 'pack'. This will only return true if the user has access to
     * a pack or specific content. If the user is a 'member' (has all content access) but has no packs then this
     * will still return false.
     *
     * @param $userId
     * @return bool
     * @throws ORMException
     */
    public function hasSpecificContentAccess($userId)
    {
        foreach ($this->getUsersProducts($userId) as $userProduct) {
            if ($userProduct->isValid() && $userProduct->getProduct()->isSpecificContentAccessProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * In legacy terminology this returns whether the user is a 'lifetime member'. This means they have a membership
     * product attached to their account which has no expiration date. NOTE: this can return true even if the
     * user has a membership product attached which is not explicity a 'lifetime' product by SKU. For example
     * even if the user has a 1-month recurring membership product but for whatever reason is expiration date is
     * null, this will return true.
     *
     * @param $userId
     * @return bool
     * @throws ORMException
     */
    public function hasLifetimeAllContentAccess($userId)
    {
        foreach ($this->getUsersProducts($userId) as $userProduct) {
            if ($userProduct->isValid() &&
                $userProduct->getProduct()->isAllContentAccessProduct() &&
                empty($userProduct->getExpirationDate())) {
                return true;
            }
        }

        return false;
    }

    /**
     * In legacy terminology this will return true only if the user has no valid 'membership' (all content access)
     * products but has a valid 'pack' (specific content access) product. This can be true when a user has only
     * purchased a pack, or they were a member who also had a pack but their membership expired or was cancelled.
     *
     * @param $userId
     * @return bool
     * @throws ORMException
     */
    public function hasSpecificContentAccessOnly($userId)
    {
        return !$this->hasAllContentAccess($userId) && $this->hasSpecificContentAccess($userId);
    }

    /**
     * This returns true if the user had a valid all content access product (membership) in the past, but its
     * expiration date is in the past, or it was deleted.
     *
     * This will return false if the user never had an all content access product (membership) ever.
     *
     * WARNING: this is not related to recurring subscription status. For example if the users all content access
     * subscription has failed/cancelled, but they have since purchased 30 days of one-time access
     * and that access is valid, this will return false even though their recurring subscription is expired.
     *
     * @param $userId
     * @return bool
     * @throws ORMException
     */
    public function hasExpiredAllContentAccess($userId)
    {
        if ($this->hasAllContentAccess($userId)) {
            return false;
        }

        foreach ($this->getUsersProducts($userId) as $userProduct) {
            if ($userProduct->isExpired() && $userProduct->getProduct()->isAllContentAccessProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the users most relevant/representing all content access (membership) user product. This returns which
     * user product best represents the current state of the users account.
     *
     * If the user has any valid 'all content access' products, for each valid return the one with no expiration date
     * otherwise return the one with the expiration date furthest out in time.
     *
     * If there are no valid 'all content access' products, for each invalid 'all content access' products,
     * return the one with the expiration date the furthest out in time.
     *
     * @param $userId
     * @return UserProduct
     * @throws ORMException
     */
    public function getUsersRepresentingAllContentAccessProduct($userId)
    {
        $representingUserProduct = null;

        // check valid user products first
        if ($this->hasAllContentAccess($userId)) {
            foreach ($this->getUsersProducts($userId) as $userProduct) {
                // always exclude deleted and invalid user products from this algo
                if (!empty($userProduct->getDeletedAt()) || !$userProduct->isValid()) {
                    continue;
                }

                // always use the lifetime product if there is one
                if ($userProduct->neverExpires()) {
                    $representingUserProduct = $userProduct;

                    break;
                }

                // otherwise, use the one with the largest expiration date
                if (empty($representingUserProduct) ||
                    (!empty($userProduct->getExpirationDate()) &&
                        !empty($representingUserProduct->getExpirationDate()) &&
                        $userProduct->getExpirationDate() > $representingUserProduct->getExpirationDate())
                ) {
                    $representingUserProduct = $userProduct;
                }
            }
        }

        // if no valid products are found, check expired ones and return the one with the largest expiration date
        if (empty($representingUserProduct)) {
            foreach ($this->getUsersProducts($userId) as $userProduct) {
                // always exclude deleted and valid user products from this algo
                if (!empty($userProduct->getDeletedAt()) || $userProduct->isValid()) {
                    continue;
                }

                // use the one with the largest expiration date
                if (empty($representingUserProduct) ||
                    (!empty($userProduct->getExpirationDate()) &&
                        !empty($representingUserProduct->getExpirationDate()) &&
                        $userProduct->getExpirationDate() > $representingUserProduct->getExpirationDate())
                ) {
                    $representingUserProduct = $userProduct;
                }
            }
        }

        return $representingUserProduct;
    }

    /**
     * Returns carbon obj, or null if it's a lifetime product, or false if they have never had
     * an all content access product.
     *
     * @param $userId
     * @return DateTimeInterface|null|false
     * @throws ORMException
     */
    public function getAllContentAccessExpirationDate($userId)
    {
        $userProduct = $this->getUsersRepresentingAllContentAccessProduct($userId);

        if (empty($userProduct)) {
            return false;
        }

        return $userProduct->getExpirationDate();
    }
}
