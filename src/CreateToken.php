<?php

namespace Yosmy\Payment;

use Yosmy\Owner;
use Yosmy\Stripe;

/**
 * @di\service()
 */
class CreateToken
{
    /**
     * @var Stripe\CreateToken
     */
    private $createToken;

    /**
     * @var Owner\Log\AddEvent
     */
    private $addEvent;

    /**
     * @param Stripe\CreateToken $createToken
     * @param Owner\Log\AddEvent $addEvent
     */
    public function __construct(
        Stripe\CreateToken $createToken,
        Owner\Log\AddEvent $addEvent
    ) {
        $this->createToken = $createToken;
        $this->addEvent = $addEvent;
    }

    /**
     * @param string $client
     * @param string $number
     * @param string $name
     * @param string $month
     * @param string $year
     * @param string $cvc
     *
     * @return array
     *
     * @throws Exception
     */
    public function create(
        string $client,
        string $number,
        string $name,
        string $month,
        string $year,
        string $cvc
    ) {
        try {
            $card = $this->createToken->create(
                $number,
                $name,
                $month,
                $year,
                $cvc
            );
        } catch (Stripe\Exception $e) {
            throw new Exception(
                $e->getType(),
                $e->getCode()
            );
        }

        $this->addEvent->add(
            $client,
            'payment.create_token.success',
            [
                'number' => $number,
                'name' => $name,
                'month' => $month,
                'year' => $year,
                'cvc' => $cvc,
            ],
            $card
        );

        return $card;
    }
}