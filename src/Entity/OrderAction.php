<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderAction
 *
 * @ORM\Table(name="order_action", indexes={@ORM\Index(name="order_id", columns={"order_id"})})
 * @ORM\Entity
 */
class OrderAction
{
    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $orderId;

    /**
     * @var int
     *
     * @ORM\Column(name="index", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $index;

    /**
     * @var bool
     *
     * @ORM\Column(name="action_buy", type="boolean", nullable=false)
     */
    private $actionBuy;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", precision=10, scale=0, nullable=false)
     */
    private $quantity;

    /**
     * @var int
     *
     * @ORM\Column(name="price", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $price;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $amount;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="time", type="datetime", nullable=true, options={"default"="NULL"})
     */
    private $time = 'NULL';


}
