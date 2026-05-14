<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;

class AttachmentControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Attachment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\TicketLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Ticket')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Attachment User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTicket(User $user): Ticket
    {
        $ticket = new Ticket();
        $ticket->setTitle('Attachment Ticket');
        $ticket->setStatus('OPEN');
        $ticket->setPriority('MEDIUM');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    public function testUploadAttachment(): void
    {
        $user = $this->createUser('attach_test@example.com');
        $ticket = $this->createTicket($user);
        
        $this->client->loginUser($user);

        // Criar um arquivo temporário para upload
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');
        
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $this->client->request(
            'POST',
            "/api/tickets/{$ticket->getId()}/attachments",
            [],
            ['file' => $uploadedFile]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('test.txt', $data['filename']);
        
        // Limpar arquivo temporário
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
