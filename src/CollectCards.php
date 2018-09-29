<?php

namespace Yosmy\Payment;

use Yosmy\Stripe;
use Yosmy\Owner;

/**
 * @di\service()
 */
class CollectCards
{
    /**
     * @var Owner\Stripe\PickProfile
     */
    private $pickStripeProfile;

    /**
     * @var Stripe\CollectCards
     */
    private $collectCards;

    /**
     * @param Owner\Stripe\PickProfile $pickStripeProfile
     * @param Stripe\CollectCards $collectCards
     */
    public function __construct(
        Owner\Stripe\PickProfile $pickStripeProfile,
        Stripe\CollectCards $collectCards
    ) {
        $this->pickStripeProfile = $pickStripeProfile;
        $this->collectCards = $collectCards;
    }

    /**
     * @param string $client
     *
     * @return Stripe\Card[]
     */
    public function collect($client)
    {
        try {
            $profile = $this->pickStripeProfile->pick($client);
        } catch (Owner\Stripe\Profile\NonexistentException $e) {
            return [];
        }

        return $this->collectCards->collect($profile->getCustomer());
    }
}