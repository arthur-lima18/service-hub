<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class CommentControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Comment')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\TicketLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Ticket')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Comment User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTicket(User $user): Ticket
    {
        $ticket = new Ticket();
        $ticket->setTitle('Comment Ticket');
        $ticket->setStatus('OPEN');
        $ticket->setPriority('MEDIUM');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    public function testAddComment(): void
    {
        $user = $this->createUser('comment_test@example.com');
        $ticket = $this->createTicket($user);
        
        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            "/api/tickets/{$ticket->getId()}/comments",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['content' => 'This is a test comment'])
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('This is a test comment', $data['content']);
    }

    public function testListComments(): void
    {
        $user = $this->createUser('list_comment_test@example.com');
        $ticket = $this->createTicket($user);

        $comment = new Comment();
        $comment->setContent('Existing comment');
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setAuthor($user);
        $comment->setTicket($ticket);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', "/api/tickets/{$ticket->getId()}/comments");

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $data);
        $this->assertEquals('Existing comment', $data[0]['content']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
