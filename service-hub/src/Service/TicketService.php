<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\TicketLog;
use App\Exception\APIException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TicketService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private UserRepository $userRepository
    ) {
    }

    public function create(array $data, $user): Ticket
    {
        $ticket = new Ticket();
        $ticket->setTitle($data['title'] ?? '');
        $ticket->setDescription($data['description'] ?? '');
        $ticket->setStatus('OPEN');
        $ticket->setPriority($data['priority'] ?? 'MEDIUM');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->em->persist($ticket);

        $log = new TicketLog();
        $log->setAction('CREATED');
        $log->setDescription('Ticket criado');
        $log->setTicket($ticket);

        $this->em->persist($log);

        $this->em->flush();

        $this->sendEvent('TICKET_CREATED', [
            'id' => $ticket->getId(),
            'title' => $ticket->getTitle()
        ]);

        return $ticket;
    }

    public function update(int $id, array $data, $user): Ticket
    {
        $ticket = $this->em->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            throw new APIException('Ticket não encontrado', 404);
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $isOwner = $ticket->getCreatedBy() === $user;
        $isAssignee = $ticket->getAssignedTo() === $user;

        if (!$isOwner && !$isAssignee && !$isAdmin) {
            throw new APIException('Acesso negado', 403);
        }

        if ($ticket->getStatus() === 'CLOSED' && !isset($data['status'])) {
            throw new APIException('Ticket fechado não pode ser alterado', 400);
        }

        if (isset($data['status'])) {
            $oldStatus = $ticket->getStatus();

            if ($oldStatus !== $data['status']) {
                $ticket->setStatus($data['status']);

                $this->log($ticket, 'STATUS_CHANGED', "De $oldStatus para {$data['status']}");

                $this->sendEvent('TICKET_STATUS_CHANGED', [
                    'ticketId' => $ticket->getId(),
                    'old' => $oldStatus,
                    'new' => $data['status']
                ]);
            }
        }

        if (isset($data['priority'])) {
            if (!$isAdmin && $ticket->getStatus() === 'CLOSED') {
                throw new APIException('Não é possível alterar a prioridade de um ticket fechado', 400);
            }

            $oldPriority = $ticket->getPriority();

            if ($oldPriority !== $data['priority']) {
                $ticket->setPriority($data['priority']);

                $this->log($ticket, 'PRIORITY_CHANGED', "De $oldPriority para {$data['priority']}");

                $this->sendEvent('TICKET_PRIORITY_CHANGED', [
                    'ticketId' => $ticket->getId(),
                    'old' => $oldPriority,
                    'new' => $data['priority']
                ]);
            }
        }

        if (isset($data['assignedTo'])) {
            if (!$isAdmin) {
                throw new APIException('Apenas administradores podem atribuir tickets', 403);
            }

            $userAssigned = $this->userRepository->find($data['assignedTo']);

            if (!$userAssigned) {
                throw new APIException('Usuário não encontrado', 404);
            }

            $oldAssigned = $ticket->getAssignedTo()?->getId();

            if ($oldAssigned !== $userAssigned->getId()) {
                $ticket->setAssignedTo($userAssigned);

                $this->log($ticket, 'ASSIGNED_CHANGED', "De $oldAssigned para {$userAssigned->getId()}");

                $this->sendEvent('TICKET_ASSIGNED', [
                    'ticketId' => $ticket->getId(),
                    'old' => $oldAssigned,
                    'new' => $userAssigned->getId()
                ]);
            }
        }

        $ticket->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $ticket;
    }

    private function log(Ticket $ticket, string $action, string $description): void
    {
        $log = new TicketLog();
        $log->setAction($action);
        $log->setDescription($description);
        $log->setTicket($ticket);

        $this->em->persist($log);
    }

    private function sendEvent(string $event, array $data): void
    {
        try {
            $this->httpClient->request('POST', 'http://localhost:8081/notifications', [
                'json' => [
                    'event' => $event,
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                    'data' => $data
                ]
            ]);
        } catch (\Throwable $e) {
            throw new APIException('Erro ao enviar evento', 404);
        }
    }
}