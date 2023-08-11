<?php

namespace Railroad\Referral\Events;

use Railroad\Referral\Models\Referrer;

class AugustContestReferralClaimed
{
    /**
     * ReferralClaimed constructor.
     */
    public function __construct(Referrer $referrer, int $productId, int $userId)
    {
        $this->referrer = $referrer;
        $this->productId = $productId;
        $this->userId = $userId;
    }

    /**
     * @return Referrer
     */
    public function getReferrer(): Referrer
    {
        return $this->referrer;
    }

    /**
     * @param Referrer $referrer
     */
    public function setReferrer(Referrer $referrer): void
    {
        $this->referrer = $referrer;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     */
    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

}
