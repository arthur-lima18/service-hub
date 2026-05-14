<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Attachment;
use App\Response\APIResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/tickets/{ticketId}/attachments')]
class AttachmentController extends AbstractController
{
    use APIResponse;

    #[Route('', methods: ['POST'])]
    public function upload(
        int $ticketId,
        Request $request,
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        SluggerInterface $slugger
    ): JsonResponse {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->error('Ticket não encontrado', 404);
        }

        $file = $request->files->get('file');

        if (!$file) {
            return $this->error('Nenhum arquivo enviado');
        }

        // Validação de segurança
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getClientMimeType(), $allowedMimeTypes)) {
            return $this->error('Tipo de arquivo não permitido. Use PDF ou Imagens (JPG, PNG, WEBP).', 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->error('O arquivo é muito grande. O limite é de 5MB.', 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir').'/public/uploads',
                $newFilename
            );
        } catch (\Exception $e) {
            return $this->error('Erro ao salvar o arquivo: ' . $e->getMessage());
        }

        $attachment = new Attachment();
        $attachment->setFilename($file->getClientOriginalName());
        $attachment->setFilePath('/uploads/'.$newFilename);
        $attachment->setMimeType($file->getClientMimeType());
        $attachment->setFileSize($file->getSize());
        $attachment->setCreatedAt(new \DateTimeImmutable());
        $attachment->setUploadedBy($this->getUser());
        $attachment->setTicket($ticket);

        $em->persist($attachment);
        $em->flush();

        return $this->success($serializer, $attachment, 'attachment:read');
    }

    #[Route('', methods: ['GET'])]
    public function list(int $ticketId, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $ticket = $em->getRepository(Ticket::class)->find($ticketId);

        if (!$ticket) {
            return $this->error('Ticket não encontrado', 404);
        }

        return $this->success($serializer, $ticket->getAttachments(), 'attachment:read');
    }
}
