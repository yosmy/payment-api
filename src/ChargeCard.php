<?php

namespace Yosmy\Payment;

use Yosmy\Stripe;
use Yosmy\Owner;

/**
 * @di\service()
 */
class ChargeCard
{
    /**
     * @var Stripe\CalculateFee
     */
    private $calculateFee;

    /**
     * @var Owner\Stripe\PickProfile
     */
    private $pickProfile;

    /**
     * @var Stripe\PickCard
     */
    private $pickCard;

    /**
     * @var Stripe\ChargeCard
     */
    private $chargeCard;

    /**
     * @var Owner\Log\AddEvent
     */
    private $addEvent;

    /**
     * @param Stripe\CalculateFee $calculateFee
     * @param Owner\Stripe\PickProfile $pickProfile
     * @param Stripe\PickCard $pickCard
     * @param Stripe\ChargeCard $chargeCard
     * @param Owner\Log\AddEvent $addEvent
     */
    public function __construct(
        Stripe\CalculateFee $calculateFee,
        Owner\Stripe\PickProfile $pickProfile,
        Stripe\PickCard $pickCard,
        Stripe\ChargeCard $chargeCard,
        Owner\Log\AddEvent $addEvent
    ) {
        $this->calculateFee = $calculateFee;
        $this->pickProfile = $pickProfile;
        $this->pickCard = $pickCard;
        $this->chargeCard = $chargeCard;
        $this->addEvent = $addEvent;
    }

    /**
     * @param string $owner
     * @param string $description
     * @param int $amount
     * @param string $card
     *
     * @throws Exception
     */
    function charge(
        string $owner,
        string $description,
        int $amount,
        string $card
    ) {
        // Plus Stripe fee
        $amount = $amount + $this->calculateFee->calculate($amount);
        // Stripe works with cents
        $amount = $amount * 100;
        $currency = 'usd';

        try {
            $customer = $this->pickProfile
                ->pick($owner)
                ->getCustomer();

            $card = $this->pickCard
                ->pick($customer, $card)
                ->getId();
        }
        // No stripe profile. The client wants to use the card without saving it
        catch (Owner\Stripe\Profile\NonexistentException $e) {
            $customer = null;
        }
        // The client has stripe profile, but we can't find a card with that id
        // Then it's a one-time charge token
        catch (Stripe\NonexistentCardException $e) {
            $customer = null;
        }

        try {
            $result = $this->chargeCard->charge(
                $customer,
                $amount,
                $currency,
                $description,
                $card
            );

            $this->addEvent->add(
                $owner,
                'payment.charge_card.success',
                [
                    'customer' => $customer,
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => $description,
                    'card' => $card,
                ],
                $result
            );
        } catch (Stripe\Exception $e) {
            $this->addEvent->add(
                $owner,
                'payment.charge_card.error',
                [
                    'customer' => $customer,
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => $description,
                    'card' => $card,
                ],
                $e->jsonSerialize()
            );

            throw new Exception(
                $e->getType(),
                $e->getCode()
            );
        }
    }
}