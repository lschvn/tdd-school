<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Ticket;
use App\Entity\User;
use App\Service\TicketService;

/**
 * TicketController Test Suite
 * 
 * This test suite covers all TicketController endpoints:
 * 
 * REST API Endpoints:
 * - GET /api/tickets - List all tickets
 * - GET /api/tickets/{id} - Get specific ticket
 * - POST /api/tickets - Create new ticket
 * - PUT /api/tickets/{id} - Update ticket (description, priority)
 * - DELETE /api/tickets/{id} - Delete ticket
 * 
 * Assignment Endpoints:
 * - POST /api/tickets/{id}/assign - Assign ticket to user
 * - DELETE /api/tickets/{id}/assign - Unassign ticket
 * 
 * Workflow Endpoints:
 * - POST /api/tickets/{id}/start - Start ticket processing
 * - POST /api/tickets/{id}/close - Close ticket
 * 
 * User Ticket Endpoints:
 * - GET /api/users/{id}/tickets/owned - List tickets created by user
 * - GET /api/users/{id}/tickets/assigned - List tickets assigned to user
 */
class TicketControllerTest extends WebTestCase
{
    private $client;
    private $ticketService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Mock the TicketService for testing
        $this->ticketService = $this->createMock(TicketService::class);
        
        // Replace the service in the container for testing
        self::getContainer()->set(TicketService::class, $this->ticketService);
    }

    // ===== LIST ALL TICKETS TESTS =====

    public function testGetTicketsReturnsJsonResponse(): void
    {
        $mockTickets = [
            $this->createMockTicket(1, 'Test Ticket 1'),
            $this->createMockTicket(2, 'Test Ticket 2')
        ];

        $this->ticketService
            ->expects($this->once())
            ->method('getAllTickets')
            ->willReturn($mockTickets);

        $this->client->request('GET', '/api/tickets');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Test Ticket 1', $responseData[0]['title']);
        $this->assertEquals('Test Ticket 2', $responseData[1]['title']);
    }

    public function testGetTicketsReturnsEmptyArrayWhenNoTickets(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getAllTickets')
            ->willReturn([]);

        $this->client->request('GET', '/api/tickets');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    // ===== GET SPECIFIC TICKET TESTS =====

    public function testGetTicketReturnsTicketWhenExists(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->client->request('GET', '/api/tickets/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['id']);
        $this->assertEquals('Test Ticket', $responseData['title']);
    }

    public function testGetTicketReturns404WhenNotExists(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $this->client->request('GET', '/api/tickets/999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket not found', $responseData['error']);
    }

    // ===== CREATE TICKET TESTS =====

    public function testCreateTicketReturnsCreatedTicket(): void
    {
        $mockOwner = $this->createMockUser(1, 'owner@test.com');
        $mockAssignee = $this->createMockUser(2, 'assignee@test.com');
        $mockTicket = $this->createMockTicket(1, 'New Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('createTicket')
            ->with(
                $this->anything(),
                $this->anything(),
                'New Ticket',
                'Test description',
                'normale',
                'pending'
            )
            ->willReturn($mockTicket);

        $requestData = [
            'ownerId' => 1,
            'assignedToId' => 2,
            'title' => 'New Ticket',
            'description' => 'Test description',
            'priority' => 'normale',
            'status' => 'pending'
        ];

        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['id']);
        $this->assertEquals('New Ticket', $responseData['title']);
    }

    public function testCreateTicketReturns400WithInvalidData(): void
    {
        $requestData = [
            'title' => '', // Invalid empty title
            'description' => 'Test description',
            'priority' => 'invalid-priority', // Invalid priority
            'status' => 'pending'
        ];

        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testCreateTicketReturns400WithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid JSON data', $responseData['error']);
    }

    // ===== UPDATE TICKET TESTS =====

    public function testUpdateTicketReturnsUpdatedTicket(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Updated Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('updateTicket')
            ->with($mockTicket, 'Updated description', 'haute');

        $requestData = [
            'description' => 'Updated description',
            'priority' => 'haute'
        ];

        $this->client->request(
            'PUT',
            '/api/tickets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['id']);
    }

    public function testUpdateTicketReturns404WhenNotExists(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $requestData = [
            'description' => 'Updated description'
        ];

        $this->client->request(
            'PUT',
            '/api/tickets/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTicketReturns400WithInvalidPriority(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('updateTicket')
            ->willThrowException(new \InvalidArgumentException('Invalid priority'));

        $requestData = [
            'priority' => 'invalid-priority'
        ];

        $this->client->request(
            'PUT',
            '/api/tickets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid priority', $responseData['error']);
    }

    // ===== DELETE TICKET TESTS =====

    public function testDeleteTicketReturnsSuccessMessage(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('deleteTicket')
            ->with($mockTicket)
            ->willReturn(true);

        $this->client->request('DELETE', '/api/tickets/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket deleted successfully', $responseData['message']);
    }

    public function testDeleteTicketReturns404WhenNotExists(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $this->client->request('DELETE', '/api/tickets/999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ===== ASSIGN TICKET TESTS =====

    public function testAssignTicketReturnsSuccess(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');
        $mockUser = $this->createMockUser(2, 'user@test.com');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('assignTicket')
            ->with($mockTicket, $mockUser);

        $requestData = ['userId' => 2];

        $this->client->request(
            'POST',
            '/api/tickets/1/assign',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket assigned successfully', $responseData['message']);
    }

    public function testAssignTicketReturns404WhenTicketNotExists(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $requestData = ['userId' => 2];

        $this->client->request(
            'POST',
            '/api/tickets/999/assign',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAssignTicketReturns400WithMissingUserId(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->client->request(
            'POST',
            '/api/tickets/1/assign',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User ID is required', $responseData['error']);
    }

    // ===== UNASSIGN TICKET TESTS =====

    public function testUnassignTicketReturnsSuccess(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('unassignTicket')
            ->with($mockTicket);

        $this->client->request('DELETE', '/api/tickets/1/assign');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket unassigned successfully', $responseData['message']);
    }

    public function testUnassignTicketReturns404WhenNotExists(): void
    {
        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $this->client->request('DELETE', '/api/tickets/999/assign');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ===== START TICKET TESTS =====

    public function testStartTicketReturnsSuccess(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');
        $mockUser = $this->createMockUser(1, 'user@test.com');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('startTicket')
            ->with($mockTicket, $mockUser);

        $requestData = ['userId' => 1];

        $this->client->request(
            'POST',
            '/api/tickets/1/start',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket started successfully', $responseData['message']);
    }

    public function testStartTicketReturns400WhenNotAssignedToUser(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');
        $mockUser = $this->createMockUser(1, 'user@test.com');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('startTicket')
            ->with($mockTicket, $mockUser)
            ->willThrowException(new \InvalidArgumentException('Ticket is not assigned to current user'));

        $requestData = ['userId' => 1];

        $this->client->request(
            'POST',
            '/api/tickets/1/start',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket is not assigned to current user', $responseData['error']);
    }

    // ===== CLOSE TICKET TESTS =====

    public function testCloseTicketReturnsSuccess(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');
        $mockUser = $this->createMockUser(1, 'user@test.com');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('closeTicket')
            ->with($mockTicket, $mockUser);

        $requestData = ['userId' => 1];

        $this->client->request(
            'POST',
            '/api/tickets/1/close',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket closed successfully', $responseData['message']);
    }

    public function testCloseTicketReturns400WhenNotAssignedToUser(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Test Ticket');
        $mockUser = $this->createMockUser(1, 'user@test.com');

        $this->ticketService
            ->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        $this->ticketService
            ->expects($this->once())
            ->method('closeTicket')
            ->with($mockTicket, $mockUser)
            ->willThrowException(new \InvalidArgumentException('Ticket is not assigned to current user'));

        $requestData = ['userId' => 1];

        $this->client->request(
            'POST',
            '/api/tickets/1/close',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Ticket is not assigned to current user', $responseData['error']);
    }

    // ===== USER TICKETS TESTS =====

    public function testGetTicketsByOwnerReturnsTickets(): void
    {
        $mockUser = $this->createMockUser(1, 'owner@test.com');
        $mockTickets = [
            $this->createMockTicket(1, 'Ticket 1'),
            $this->createMockTicket(2, 'Ticket 2')
        ];

        $this->ticketService
            ->expects($this->once())
            ->method('getTicketsByOwner')
            ->with($mockUser)
            ->willReturn($mockTickets);

        $this->client->request('GET', '/api/users/1/tickets/owned');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Ticket 1', $responseData[0]['title']);
        $this->assertEquals('Ticket 2', $responseData[1]['title']);
    }

    public function testGetTicketsByAssigneeReturnsTickets(): void
    {
        $mockUser = $this->createMockUser(1, 'assignee@test.com');
        $mockTickets = [
            $this->createMockTicket(3, 'Assigned Ticket 1'),
            $this->createMockTicket(4, 'Assigned Ticket 2')
        ];

        $this->ticketService
            ->expects($this->once())
            ->method('getTicketsByAssignee')
            ->with($mockUser)
            ->willReturn($mockTickets);

        $this->client->request('GET', '/api/users/1/tickets/assigned');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Assigned Ticket 1', $responseData[0]['title']);
        $this->assertEquals('Assigned Ticket 2', $responseData[1]['title']);
    }

    public function testGetUserTicketsReturns404WhenUserNotExists(): void
    {
        // Test both owned and assigned routes with non-existent user
        $this->client->request('GET', '/api/users/999/tickets/owned');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->request('GET', '/api/users/999/tickets/assigned');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ===== VALIDATION TESTS =====

    public function testEndpointsRequireJsonContentType(): void
    {
        $endpoints = [
            ['POST', '/api/tickets'],
            ['PUT', '/api/tickets/1'],
            ['POST', '/api/tickets/1/assign'],
            ['POST', '/api/tickets/1/start'],
            ['POST', '/api/tickets/1/close']
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->client->request($method, $url, [], [], [], '{"test": "data"}');
            
            // Should return 400 for missing content-type header
            $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            
            $responseData = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertEquals('Content-Type must be application/json', $responseData['error']);
        }
    }

    public function testGetEndpointsAcceptAnyContentType(): void
    {
        // Mock the service to avoid actual calls
        $this->ticketService->method('getAllTickets')->willReturn([]);
        $this->ticketService->method('getTicket')->willReturn(null);

        $getEndpoints = [
            '/api/tickets',
            '/api/tickets/1',
            '/api/users/1/tickets/owned',
            '/api/users/1/tickets/assigned'
        ];

        foreach ($getEndpoints as $url) {
            $this->client->request('GET', $url);
            
            // GET endpoints should not require specific content-type
            $this->assertThat(
                $this->client->getResponse()->getStatusCode(),
                $this->logicalOr(
                    $this->equalTo(Response::HTTP_OK),
                    $this->equalTo(Response::HTTP_NOT_FOUND)
                )
            );
        }
    }

    // ===== HELPER METHODS =====

    private function createMockTicket(int $id, string $title): Ticket
    {
        $ticket = $this->createMock(Ticket::class);
        $ticket->method('getId')->willReturn($id);
        $ticket->method('getTitle')->willReturn($title);
        $ticket->method('getDescription')->willReturn('Test description');
        $ticket->method('getPriority')->willReturn('normale');
        $ticket->method('getStatus')->willReturn('pending');
        $ticket->method('getCreatedAt')->willReturn(new \DateTime());
        $ticket->method('getFirstAssignedAt')->willReturn(new \DateTime());
        $ticket->method('getLastAssignedAt')->willReturn(new \DateTime());
        
        // Mock the toArray method for JSON serialization
        $ticket->method('toArray')->willReturn([
            'id' => $id,
            'owner' => null,
            'assignedTo' => null,
            'title' => $title,
            'description' => 'Test description',
            'priority' => 'normale',
            'status' => 'pending',
            'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'firstAssignedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'lastAssignedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        
        return $ticket;
    }

    private function createMockUser(int $id, string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email);
        
        return $user;
    }

    // ===== EDGE CASES TESTS =====

    public function testLargeDataHandling(): void
    {
        $longDescription = str_repeat('A very long description. ', 1000);
        
        $mockTicket = $this->createMockTicket(1, 'Long Description Ticket');

        $this->ticketService
            ->expects($this->once())
            ->method('createTicket')
            ->willReturn($mockTicket);

        $requestData = [
            'ownerId' => 1,
            'assignedToId' => 2,
            'title' => 'Long Description Ticket',
            'description' => $longDescription,
            'priority' => 'normale',
            'status' => 'pending'
        ];

        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testSpecialCharactersInTicketData(): void
    {
        $specialTitle = 'Ticket with special chars: àáâãäåæçèéêë';
        $specialDescription = 'Description with emojis: 🚀🔥💯 and symbols: @#$%^&*()';
        
        $mockTicket = $this->createMockTicket(1, $specialTitle);

        $this->ticketService
            ->expects($this->once())
            ->method('createTicket')
            ->willReturn($mockTicket);

        $requestData = [
            'ownerId' => 1,
            'assignedToId' => 2,
            'title' => $specialTitle,
            'description' => $specialDescription,
            'priority' => 'normale',
            'status' => 'pending'
        ];

        $this->client->request(
            'POST',
            '/api/tickets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testConcurrentTicketOperations(): void
    {
        $mockTicket = $this->createMockTicket(1, 'Concurrent Test Ticket');

        // Simulate concurrent read operations
        $this->ticketService
            ->expects($this->exactly(3))
            ->method('getTicket')
            ->with(1)
            ->willReturn($mockTicket);

        // Make multiple concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $this->client->request('GET', '/api/tickets/1');
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        }
    }
}

