<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Structures\SubscriptionStateInterval;
use Railroad\Ecommerce\Entities\Subscription;

class MembershipStatsService
{
    /**
     * returns subscription state intervals
     *
     * @param Subscription $subscription
     * @param Carbon $statsStart
     * @param Carbon $statsEnd
     *
     * @return array
     */
    public function getSubscriptionStateIntervals(
        Subscription $subscription,
        Carbon $statsStart,
        Carbon $statsEnd
    ): array
    {
        $intervals = [];

        if ($subscription->getPaidUntil() > $statsStart) {
            $intervalActiveStart = $subscription->getStartDate() < $statsStart ?
                                        $statsStart : $subscription->getStartDate();

            $intervalActiveEnd = $subscription->getPaidUntil() > $statsEnd ? $statsEnd : $subscription->getPaidUntil();

            $intervals[] = new SubscriptionStateInterval(
                $intervalActiveStart,
                $intervalActiveEnd,
                SubscriptionStateInterval::TYPE_ACTIVE
            );
        }

        if (
            $subscription->getPaidUntil() < $statsEnd
            && (
                !$subscription->getCanceledOn()
                || $subscription->getCanceledOn() > $subscription->getPaidUntil()
            )
        ) {
            $intervalSuspendedStart = $subscription->getPaidUntil();

            $intervalSuspendedEnd = $subscription->getCanceledOn() && $subscription->getCanceledOn() < $statsEnd ?
                                        $subscription->getCanceledOn() : $statsEnd;

            $intervals[] = new SubscriptionStateInterval(
                $intervalSuspendedStart,
                $intervalSuspendedEnd,
                SubscriptionStateInterval::TYPE_SUSPENDED
            );
        }

        if ($subscription->getCanceledOn() && $subscription->getCanceledOn() < $statsEnd) {
            $intervalCanceledStart = $subscription->getCanceledOn();

            $intervalCanceledEnd = $subscription->getCanceledOn() < $statsEnd ?
                                        $subscription->getCanceledOn() : $statsEnd;

            $intervals[] = new SubscriptionStateInterval(
                $intervalCanceledStart,
                $intervalCanceledEnd,
                SubscriptionStateInterval::TYPE_CANCELED
            );
        }

        return $intervals;
    }

    /**
     * returns subscription state intervals added to the existing intervals
     *
     * @param Subscription $subscription
     * @param Carbon $statsStart
     * @param Carbon $startsEnd
     * @param array $existing
     *
     * @return array
     */
    public function addSubscriptionStateIntervals(
        Subscription $subscription,
        Carbon $statsStart,
        Carbon $startsEnd,
        array $existing
    ): array
    {
        $intervals = $this->getSubscriptionStateIntervals(
            $subscription,
            $statsStart,
            $startsEnd
        );

        foreach ($intervals as $newSubStateInt) {

            $matchedAndIgnore = false;

            foreach ($existing as $index => $existingSubStateInt) {
                if (
                    (
                        $newSubStateInt->getEnd() > $existingSubStateInt->getStart()
                        && $newSubStateInt->getEnd() < $existingSubStateInt->getEnd()
                    )
                    || (
                        $newSubStateInt->getStart() < $existingSubStateInt->getEnd()
                        && $newSubStateInt->getEnd() > $existingSubStateInt->getEnd()
                    )
                ) {
                    // if new interval overlaps existing

                    if ($newSubStateInt->getType() == SubscriptionStateInterval::TYPE_ACTIVE) {
                        if ($existingSubStateInt->getType() == SubscriptionStateInterval::TYPE_ACTIVE) {
                            // if both active, use only the one with bigger end date
                            if ($newSubStateInt->getEnd() < $existingSubStateInt->getEnd()) {
                                $matchedAndIgnore = true;
                            } else {
                                unset($existing[$index]);
                            }
                        } else {
                            // shorten the existing suspended/canceled state
                            if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() < $existingSubStateInt->getEnd()
                            ) {
                                /*
                                [   ]   -> newSubStateInt
                                  [   ] -> existingSubStateInt
                                */
                                $existingSubStateInt->setStart($newSubStateInt->getEnd());
                            } else if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getEnd()
                                && $newSubStateInt->getStart() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                   [   ]   -> newSubStateInt
                                [    ]     -> existingSubStateInt
                                and
                                   [   ]   -> newSubStateInt
                                [        ] -> existingSubStateInt
                                will set
                                   [   ]   -> newSubStateInt
                                [  ]       -> existingSubStateInt
                                */
                                $existingSubStateInt->setEnd($newSubStateInt->getStart());
                            } else {
                                // new state includes the existing interval
                                /*
                                [     ] -> newSubStateInt
                                  [ ]   -> existingSubStateInt
                                */
                                unset($existing[$index]);
                            }
                        }
                    } else if ($newSubStateInt->getType() == SubscriptionStateInterval::TYPE_SUSPENDED) {
                        if ($existingSubStateInt->getType() == SubscriptionStateInterval::TYPE_ACTIVE) {
                            // shorten the new suspended state
                            if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                [   ]      -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                and
                                [        ] -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                will set
                                [ ]        -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                */
                                $newSubStateInt->setEnd($newSubStateInt->getStart());
                            } else if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getEnd()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getEnd()
                                && $newSubStateInt->getStart() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                   [   ]   -> newSubStateInt
                                [    ]     -> existingSubStateInt
                                */
                                $newSubStateInt->setStart($existingSubStateInt->getEnd());
                            } else {
                                // new state is included in the existing interval
                                /*
                                  [  ]    -> newSubStateInt
                                [      ]  -> existingSubStateInt
                                */
                                $matchedAndIgnore = false;
                            }
                        } else if ($existingSubStateInt->getType() == SubscriptionStateInterval::TYPE_SUSPENDED) {
                            // if both suspended, use only the one with bigger end date
                            if ($newSubStateInt->getEnd() < $existingSubStateInt->getEnd()) {
                                $matchedAndIgnore = true;
                            } else {
                                unset($existing[$index]);
                            }
                        } else {
                            // shorten the existing canceled state
                            if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() < $existingSubStateInt->getEnd()
                            ) {
                                /*
                                [   ]   -> newSubStateInt
                                  [   ] -> existingSubStateInt
                                */
                                $existingSubStateInt->setStart($newSubStateInt->getEnd());
                            } else if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getEnd()
                                && $newSubStateInt->getStart() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                   [   ]   -> newSubStateInt
                                [    ]     -> existingSubStateInt
                                and
                                   [   ]   -> newSubStateInt
                                [        ] -> existingSubStateInt
                                will set
                                   [   ]   -> newSubStateInt
                                [  ]       -> existingSubStateInt
                                */
                                $existingSubStateInt->setEnd($newSubStateInt->getStart());
                            } else {
                                // new state includes the existing interval
                                /*
                                [     ] -> newSubStateInt
                                  [ ]   -> existingSubStateInt
                                */
                                unset($existing[$index]);
                            }
                        }
                    } else { // new is canceled state
                        if (
                            $existingSubStateInt->getType() == SubscriptionStateInterval::TYPE_ACTIVE
                            || $existingSubStateInt->getType() == SubscriptionStateInterval::TYPE_SUSPENDED
                        ) {
                            // shorten the new canceled state
                            if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getStart()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                [   ]      -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                and
                                [        ] -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                will set
                                [ ]        -> newSubStateInt
                                  [   ]    -> existingSubStateInt
                                */
                                $newSubStateInt->setEnd($newSubStateInt->getStart());
                            } else if (
                                $newSubStateInt->getStart() < $existingSubStateInt->getEnd()
                                && $newSubStateInt->getEnd() > $existingSubStateInt->getEnd()
                                && $newSubStateInt->getStart() > $existingSubStateInt->getStart()
                            ) {
                                /*
                                   [   ]   -> newSubStateInt
                                [    ]     -> existingSubStateInt
                                */
                                $newSubStateInt->setStart($existingSubStateInt->getEnd());
                            } else {
                                // new state is included in the existing interval
                                /*
                                  [  ]    -> newSubStateInt
                                [      ]  -> existingSubStateInt
                                */
                                $matchedAndIgnore = false;
                            }
                        } else {
                            // if both canceled, use only the one with bigger end date
                            if ($newSubStateInt->getEnd() < $existingSubStateInt->getEnd()) {
                                $matchedAndIgnore = true;
                            } else {
                                unset($existing[$index]);
                            }
                        }
                    }
                }
            }

            if (!empty($existing)) {
                $existing = array_values($existing);
            }

            if (!$matchedAndIgnore) {
                $existing[] = $newSubStateInt;
            }
        }

        return $existing;
    }
}