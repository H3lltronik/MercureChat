<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait Timestamp {
    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    /**
     * Get the value of createdAt
     */ 
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist() {
        $this->createdAt = new \DateTime();
    }
}