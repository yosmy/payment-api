<?php

namespace Yosmy\Payment;

use Yosmy\Stripe;
use Yosmy\Owner;

/**
 * @di\service()
 */
class SaveCard
{
    /**
     * @var Owner\Stripe\PickProfile
     */
    private $pickStripeProfile;

    /**
     * @var Owner\Stripe\AddProfile
     */
    private $addStripeProfile;

    /**
     * @var Stripe\CreateCustomer
     */
    private $createCustomer;

    /**
     * @var Stripe\AddCard
     */
    private $addCard;

    /**
     * @var Owner\Log\AddEvent
     */
    private $addEvent;

    /**
     * @param Owner\Stripe\PickProfile $pickStripeProfile
     * @param Owner\Stripe\AddProfile $addStripeProfile
     * @param Stripe\CreateCustomer $createCustomer
     * @param Stripe\AddCard $addCard
     * @param Owner\Log\AddEvent $addEvent
     */
    public function __construct(
        Owner\Stripe\PickProfile $pickStripeProfile,
        Owner\Stripe\AddProfile $addStripeProfile,
        Stripe\CreateCustomer $createCustomer,
        Stripe\AddCard $addCard,
        Owner\Log\AddEvent $addEvent
    ) {
        $this->pickStripeProfile = $pickStripeProfile;
        $this->addStripeProfile = $addStripeProfile;
        $this->createCustomer = $createCustomer;
        $this->addCard = $addCard;
        $this->addEvent = $addEvent;
    }

    /**
     * @param string $client
     * @param string $id
     *
     * @throws Exception
     */
    public function save(
        string $client,
        string $id
    ) {
        try {
            $profile = $this->pickStripeProfile->pick($client);

            $customer = $profile->getCustomer();
        } catch (Owner\Stripe\Profile\NonexistentException $e) {
            try {
                $customer = $this->createCustomer->create($id);
            } catch (Stripe\Exception $e) {
                throw new Exception(
                    $e->getType(),
                    $e->getCode()
                );
            }

            try {
                $this->addStripeProfile->add($client, $customer);
            } catch (Owner\Stripe\Profile\ExistentException $e) {
                throw new \LogicException();
            }
        }

        try {
            $response = $this->addCard->add($customer, $id);
        } catch (Stripe\Exception $e) {
            $this->addEvent->add(
                $client,
                'payment.save_card.error',
                [
                    'customer' => $customer,
                    'card' => $id,
                ],
                $e->jsonSerialize()
            );

            throw new Exception(
                $e->getType(),
                $e->getCode()
            );
        }

        $this->addEvent->add(
            $client,
            'payment.save_card.success',
            [
                'customer' => $customer,
                'card' => $id,
            ],
            $response
        );
    }
}