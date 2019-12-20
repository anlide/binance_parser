<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Order
 *
 * @ORM\Table(name="order_block", indexes={@ORM\Index(name="pair_id", columns={"pair_id"})})
 * @ORM\Entity
 */
class Order
{
    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $orderId;

    /**
     * @var int
     *
     * @ORM\Column(name="pair_id", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $pairId;

    /**
     * @var int
     *
     * @ORM\Column(name="result", type="integer", nullable=false)
     */
    private $result = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_date", type="datetime", nullable=false)
     */
    private $firstDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_profit", type="integer", nullable=true, options={"default"="NULL","unsigned"=true})
     */
    private $maxProfit = NULL;

    /**
     * @var int|null
     *
     * @ORM\Column(name="max_lose", type="integer", nullable=true, options={"default"="NULL","unsigned"=true})
     */
    private $maxLose = NULL;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_vasya_signal", type="boolean", nullable=true, options={"default"="NULL"})
     */
    private $isVasyaSignal = NULL;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_vasya_trend", type="boolean", nullable=true, options={"default"="NULL"})
     */
    private $isVasyaTrend = NULL;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_learning", type="boolean", nullable=true, options={"default"="NULL"})
     */
    private $isLearning = NULL;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_emotional", type="boolean", nullable=true, options={"default"="NULL"})
     */
    private $isEmotional = NULL;

    /**
     * @var int|null
     *
     * @ORM\Column(name="position_id", type="integer", nullable=true, options={"default"="NULL"})
     */
    private $positionId = NULL;

    /**
     * @param int $pairId
     * @param int $result
     * @param \DateTime $firstDateTime
     */
    public function init(int $pairId, int $result, \DateTime $firstDateTime) {
        $this->pairId = $pairId;
        $this->result = $result;
        $this->firstDate = $firstDateTime;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->orderId;
    }
}
