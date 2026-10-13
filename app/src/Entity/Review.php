<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'reviews')]
        #[ORM\JoinColumn(nullable: false)]
        private Product $product,
        #[ORM\Column(type: 'smallint')]
        private int $rating,
    ) {
    }

    public function getRating(): int
    {
        return $this->rating;
    }
}
