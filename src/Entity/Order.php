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
   * @param int $pairId
   * @param int $result
   */
  public function init(int $pairId, int $result) {
    $this->pairId = $pairId;
    $this->result = $result;
  }
}
