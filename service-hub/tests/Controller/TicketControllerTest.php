<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Ticket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class TicketControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
        
        // Limpar dados antes de cada teste
        $this->entityManager->createQuery('DELETE FROM App\Entity\TicketLog')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Ticket')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function createUser(string $email, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setRoles($roles);
        $user->setPassword('password'); // Simplificado para teste

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testCreateTicket(): void
    {
        $user = $this->createUser('test@example.com');
        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Ticket',
                'description' => 'This is a test ticket',
                'priority' => 'HIGH'
            ])
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Ticket', $data['title']);
    }

    public function testListTickets(): void
    {
        $user = $this->createUser('test_list@example.com');
        $this->client->loginUser($user);

        // Criar um ticket
        $ticket = new Ticket();
        $ticket->setTitle('List Test');
        $ticket->setDescription('Test desc');
        $ticket->setStatus('OPEN');
        $ticket->setPriority('LOW');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/tickets');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(1, $data['items']);
    }

    public function testUpdateTicketPermissions(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        
        $ticket = new Ticket();
        $ticket->setTitle('Permission Test');
        $ticket->setStatus('OPEN');
        $ticket->setPriority('LOW');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($owner);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        // Tentar editar com outro usuário (não admin/não dono)
        $this->client->loginUser($other);
        $this->client->request('PATCH', '/api/tickets/' . $ticket->getId(), [], [], [], json_encode(['status' => 'CLOSED']));
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());

        // Editar com o dono
        $this->client->loginUser($owner);
        $this->client->request('PATCH', '/api/tickets/' . $ticket->getId(), [], [], [], json_encode(['status' => 'CLOSED']));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAdminCanAssignTicket(): void
    {
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $user = $this->createUser('user@example.com');
        
        $ticket = new Ticket();
        $ticket->setTitle('Assign Test');
        $ticket->setStatus('OPEN');
        $ticket->setPriority('LOW');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $this->client->loginUser($admin);
        $this->client->request('PATCH', '/api/tickets/' . $ticket->getId(), [], [], [], json_encode(['assignedTo' => $user->getId()]));
        
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        // Verificar no banco
        $this->entityManager->refresh($ticket);
        $this->assertEquals($user->getId(), $ticket->getAssignedTo()->getId());
    }

    public function testClosedTicketCannotBeModified(): void
    {
        $user = $this->createUser('user_closed@example.com');
        
        $ticket = new Ticket();
        $ticket->setTitle('Closed Test');
        $ticket->setStatus('CLOSED');
        $ticket->setPriority('LOW');
        $ticket->setCreatedAt(new \DateTimeImmutable());
        $ticket->setCreatedBy($user);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('PATCH', '/api/tickets/' . $ticket->getId(), [], [], [], json_encode(['priority' => 'HIGH']));
        
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
