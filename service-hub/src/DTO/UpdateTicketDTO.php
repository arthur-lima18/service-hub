<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateTicketDTO
{
    #[Assert\Choice(['OPEN', 'IN_PROGRESS', 'DONE'])]
    public ?string $status = null;

    #[Assert\Choice(['LOW', 'MEDIUM', 'HIGH'])]
    public ?string $priority = null;

    public ?int $assignedTo = null;
}