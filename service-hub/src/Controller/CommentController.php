<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Comment;
use App\Response\APIResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tickets/{ticketId}/comments')]
class CommentController extends AbstractController
{
    use APIResponse;

    #[Route('', methods: ['POST'])]
    public function create(
        int $ticketId,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ): JsonResponse {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->error('Ticket não encontrado', 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (empty($data['content'])) {
            return $this->error('O conteúdo do comentário é obrigatório');
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setAuthor($this->getUser());
        $comment->setTicket($ticket);

        $em->persist($comment);
        $em->flush();

        return $this->success($serializer, $comment, 'comment:read');
    }

    #[Route('', methods: ['GET'])]
    public function list(int $ticketId, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->error('Ticket não encontrado', 404);
        }

        $comments = $em->getRepository(Comment::class)->findBy(
            ['ticket' => $ticket],
            ['createdAt' => 'ASC']
        );

        return $this->success($serializer, $comments, 'comment:read');
    }
}
