<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pair
 *
 * @ORM\Table(name="pair")
 * @ORM\Entity
 */
class Pair
{
    /**
     * @var int
     *
     * @ORM\Column(name="pair_id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $pairId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    public function init(string $name) {
        $this->name = $name;
    }

    public function getId() {
        return $this->pairId;
    }
}
