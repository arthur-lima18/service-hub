<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketLog;
use App\DTO\CreateTicketDTO;
use App\Response\APIResponse;
use App\Service\TicketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TicketController extends AbstractController
{
    use APIResponse;

    #[Route('/api/tickets', methods: ['POST'])]
    public function create(
        Request $request,
        TicketService $service,
        ValidatorInterface $validator,
        SerializerInterface $serializer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateTicketDTO();
        $dto->title = $data['title'] ?? '';
        $dto->description = $data['description'] ?? '';
        $dto->priority = $data['priority'] ?? 'MEDIUM';

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->error(iterator_to_array($errors));
        }

        $ticket = $service->create((array) $dto, $this->getUser());

        return $this->success($serializer, $ticket, 'ticket:read');
    }

    #[Route('/api/tickets', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $repo = $em->getRepository(Ticket::class);

        $status = $request->query->get('status');
        $priority = $request->query->get('priority');
        $search = $request->query->get('search');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $qb = $repo->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $priority);
        }

        if ($search) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Se não for admin, vê apenas os seus
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('t.createdBy = :user')
               ->setParameter('user', $this->getUser());
        }

        $totalItems = count($qb->getQuery()->getResult());
        
        $tickets = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($t) => [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'status' => $t->getStatus(),
            'priority' => $t->getPriority(),
            'createdAt' => $t->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $tickets);

        return new JsonResponse([
            'items' => $data,
            'meta' => [
                'total' => $totalItems,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalItems / $limit)
            ]
        ]);
    }

    #[Route('/api/tickets/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($id);

        if (!$ticket) {
            return new JsonResponse(['error' => 'Ticket não encontrado'], 404);
        }

        if ($ticket->getCreatedBy() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Acesso negado'], 403);
        }

        return new JsonResponse([
            'id' => $ticket->getId(),
            'title' => $ticket->getTitle(),
            'description' => $ticket->getDescription(),
            'status' => $ticket->getStatus(),
            'priority' => $ticket->getPriority(),
        ]);
    }

    #[Route('/api/tickets/{id}', methods: ['PATCH'])]
    public function update(int $id, Request $request, TicketService $service): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $service->update($id, $data, $this->getUser());

        return new JsonResponse(['message' => 'Ticket atualizado']);
    }

    #[Route('/api/tickets/{id}/logs', methods: ['GET'])]
    public function logs(int $id, EntityManagerInterface $em): JsonResponse
    {
        $logs = $em->getRepository(TicketLog::class)
            ->findBy(['ticket' => $id], ['createdAt' => 'DESC']);

        $data = array_map(fn($log) => [
            'action' => $log->getAction(),
            'description' => $log->getDescription(),
            'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $logs);

        return new JsonResponse($data);
    }
}