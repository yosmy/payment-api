<?php

namespace Yosmy\Payment;

use Yosmy\Owner;
use Yosmy\Stripe;

/**
 * @di\service()
 */
class DeleteCard
{
    /**
     * @var Owner\Stripe\PickProfile
     */
    private $pickStripeProfile;

    /**
     * @var Stripe\DeleteCard
     */
    private $deleteCard;

    /**
     * @var Owner\Log\AddEvent
     */
    private $addEvent;

    /**
     * @param Owner\Stripe\PickProfile $pickStripeProfile
     * @param Stripe\DeleteCard $deleteCard
     * @param Owner\Log\AddEvent $addEvent
     */
    public function __construct(
        Owner\Stripe\PickProfile $pickStripeProfile,
        Stripe\DeleteCard $deleteCard,
        Owner\Log\AddEvent $addEvent
    ) {
        $this->pickStripeProfile = $pickStripeProfile;
        $this->deleteCard = $deleteCard;
        $this->addEvent = $addEvent;
    }

    /**
     * @param string $client
     * @param string $id
     */
    public function delete(
        string $client,
        string $id
    ) {
        try {
            $profile = $this->pickStripeProfile->pick($client);
        } catch (Owner\Stripe\Profile\NonexistentException $e) {
            throw new \LogicException();
        }

        $response = $this->deleteCard->delete(
            $profile->getCustomer(),
            $id
        );

        $this->addEvent->add(
            $client,
            'payment.delete_card.success',
            [
                'customer' => $profile->getCustomer(),
                'card' => $id,
            ],
            $response
        );
    }
}