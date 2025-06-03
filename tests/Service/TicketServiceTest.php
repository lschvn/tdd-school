<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\TicketService;
use App\Entity\Ticket;
use App\Entity\User;

/**
 * TicketService Test Suite
 * 
 * This test suite covers all TicketService methods:
 * 
 * CRUD Operations:
 * - createTicket(): Create a new ticket
 * - updateTicket(): Modify ticket (description, priority)
 * - deleteTicket(): Delete a ticket
 * - getTicket(): Retrieve ticket data
 * 
 * Assignment Operations:
 * - assignTicket(): Assign ticket to a user
 * - unassignTicket(): Remove ticket assignment
 * 
 * Workflow Operations:
 * - startTicket(): Start processing an assigned ticket
 * - closeTicket(): Close an assigned ticket
 * 
 * Query Operations:
 * - getTicketsByOwner(): List tickets created by current user
 * - getTicketsByAssignee(): List tickets assigned to current user
 */

class TicketServiceTest extends TestCase
{
    private TicketService $ticketService;
    private User $mockOwner;
    private User $mockAssignedTo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks once for all tests
        $this->mockOwner = $this->createMock(User::class);
        $this->mockAssignedTo = $this->createMock(User::class);
    }

    public function testTicketServiceExists(): void
    {
        $this->assertTrue(class_exists('App\Service\TicketService'), 'TicketService class does not exist.');
    }

    public function testTicketServiceHasCreateTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('createTicket'), 'TicketService should have a createTicket method');
    }

    public function testCreateTicketReturnsTicketInstance(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();

        $ticket = $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'This is a test ticket description',
            'normale',
            'pending'
        );

        $this->assertInstanceOf(Ticket::class, $ticket);
    }

    public function testCreateTicketSetsBasicPropertiesCorrectly(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();

        $ticket = $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'This is a test ticket description',
            'normale',
            'pending'
        );

        $this->assertSame($this->mockOwner, $ticket->getOwner());
        $this->assertSame($this->mockAssignedTo, $ticket->getAssignedTo());
        $this->assertSame('Test Ticket', $ticket->getTitle());
        $this->assertSame('This is a test ticket description', $ticket->getDescription());
        $this->assertSame('normale', $ticket->getPriority());
        $this->assertSame('pending', $ticket->getStatus());
    }

    public function testCreateTicketSetsTimestampsCorrectly(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();

        $ticket = $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'This is a test ticket description',
            'normale',
            'pending'
        );

        // Check if timestamps are set and are recent (within last minute)
        $now = new \DateTime();
        $oneMinuteAgo = (clone $now)->modify('-1 minute');

        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getCreatedAt());
        $this->assertGreaterThan($oneMinuteAgo, $ticket->getCreatedAt());
        $this->assertLessThanOrEqual($now, $ticket->getCreatedAt());

        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getFirstAssignedAt());
        $this->assertGreaterThan($oneMinuteAgo, $ticket->getFirstAssignedAt());
        $this->assertLessThanOrEqual($now, $ticket->getFirstAssignedAt());

        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getLastAssignedAt());
        $this->assertGreaterThan($oneMinuteAgo, $ticket->getLastAssignedAt());
        $this->assertLessThanOrEqual($now, $ticket->getLastAssignedAt());
    }

    /**
     * @dataProvider validPriorityProvider
     */
    public function testCreateTicketWithValidPriorities(string $priority): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();

        $ticket = $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'Description',
            $priority,
            'pending'
        );

        $this->assertSame($priority, $ticket->getPriority(), "Priority '$priority' should be valid");
    }

    public static function validPriorityProvider(): array
    {
        return [
            ['basse'],
            ['normale'],
            ['haute']
        ];
    }

    /**
     * @dataProvider validStatusProvider
     */
    public function testCreateTicketWithValidStatuses(string $status): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();

        $ticket = $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'Description',
            'normale',
            $status
        );

        $this->assertSame($status, $ticket->getStatus(), "Status '$status' should be valid");
    }

    public static function validStatusProvider(): array
    {
        return [
            ['pending'],
            ['waiting'],
            ['in-progress'],
            ['done']
        ];
    }

    public function testCreateTicketWithInvalidPriorityShouldThrowException(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid priority');

        $ticketService = new TicketService();

        $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'Description',
            'invalid-priority',
            'pending'
        );
    }

    public function testCreateTicketWithInvalidStatusShouldThrowException(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');

        $ticketService = new TicketService();

        $ticketService->createTicket(
            $this->mockOwner,
            $this->mockAssignedTo,
            'Test Ticket',
            'Description',
            'normale',
            'invalid-status'
        );
    }

    // ===== UPDATE TICKET TESTS =====

    public function testTicketServiceHasUpdateTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('updateTicket'), 'TicketService should have an updateTicket method');
    }

    public function testUpdateTicketModifiesDescription(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        
        $ticket->expects($this->once())
               ->method('setDescription')
               ->with('Updated description');

        $ticketService->updateTicket($ticket, 'Updated description', null);
    }

    public function testUpdateTicketModifiesPriority(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        
        $ticket->expects($this->once())
               ->method('setPriority')
               ->with('haute');

        $ticketService->updateTicket($ticket, null, 'haute');
    }

    public function testUpdateTicketModifiesBothDescriptionAndPriority(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        
        $ticket->expects($this->once())
               ->method('setDescription')
               ->with('New description');
               
        $ticket->expects($this->once())
               ->method('setPriority')
               ->with('basse');

        $ticketService->updateTicket($ticket, 'New description', 'basse');
    }

    public function testUpdateTicketWithInvalidPriorityShouldThrowException(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid priority');

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);

        $ticketService->updateTicket($ticket, null, 'invalid-priority');
    }

    // ===== DELETE TICKET TESTS =====

    public function testTicketServiceHasDeleteTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('deleteTicket'), 'TicketService should have a deleteTicket method');
    }

    public function testDeleteTicketReturnsTrue(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);

        $result = $ticketService->deleteTicket($ticket);
        $this->assertTrue($result);
    }

    // ===== GET TICKET TESTS =====

    public function testTicketServiceHasGetTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('getTicket'), 'TicketService should have a getTicket method');
    }

    public function testGetTicketReturnsTicketById(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $ticketService->getTicket(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
    }

    public function testGetTicketWithNonExistentIdReturnsNull(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $ticketService = new TicketService();
        $ticket = $ticketService->getTicket(999999);

        $this->assertNull($ticket);
    }

    // ===== ASSIGN TICKET TESTS =====

    public function testTicketServiceHasAssignTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('assignTicket'), 'TicketService should have an assignTicket method');
    }

    public function testAssignTicketSetsAssignedToUser(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $newAssignee = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('setAssignedTo')
               ->with($newAssignee);
               
        $ticket->expects($this->once())
               ->method('setLastAssignedAt')
               ->with($this->isInstanceOf(\DateTimeInterface::class));

        $ticketService->assignTicket($ticket, $newAssignee);
    }

    public function testAssignTicketSetsFirstAssignedAtIfNotSet(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $newAssignee = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('getFirstAssignedAt')
               ->willReturn(null);
               
        $ticket->expects($this->once())
               ->method('setFirstAssignedAt')
               ->with($this->isInstanceOf(\DateTimeInterface::class));

        $ticketService->assignTicket($ticket, $newAssignee);
    }

    // ===== UNASSIGN TICKET TESTS =====

    public function testTicketServiceHasUnassignTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('unassignTicket'), 'TicketService should have an unassignTicket method');
    }

    public function testUnassignTicketRemovesAssignee(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        
        $ticket->expects($this->once())
               ->method('setAssignedTo')
               ->with(null);

        $ticketService->unassignTicket($ticket);
    }

    // ===== START TICKET TESTS =====

    public function testTicketServiceHasStartTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('startTicket'), 'TicketService should have a startTicket method');
    }

    public function testStartTicketSetsStatusToInProgress(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $currentUser = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('getAssignedTo')
               ->willReturn($currentUser);
               
        $ticket->expects($this->once())
               ->method('setStatus')
               ->with('in-progress');

        $ticketService->startTicket($ticket, $currentUser);
    }

    public function testStartTicketThrowsExceptionIfNotAssignedToCurrentUser(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ticket is not assigned to current user');

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $currentUser = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('getAssignedTo')
               ->willReturn($otherUser);

        $ticketService->startTicket($ticket, $currentUser);
    }

    // ===== CLOSE TICKET TESTS =====

    public function testTicketServiceHasCloseTicketMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('closeTicket'), 'TicketService should have a closeTicket method');
    }

    public function testCloseTicketSetsStatusToDone(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $currentUser = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('getAssignedTo')
               ->willReturn($currentUser);
               
        $ticket->expects($this->once())
               ->method('setStatus')
               ->with('done');

        $ticketService->closeTicket($ticket, $currentUser);
    }

    public function testCloseTicketThrowsExceptionIfNotAssignedToCurrentUser(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ticket is not assigned to current user');

        $ticketService = new TicketService();
        $ticket = $this->createMock(Ticket::class);
        $currentUser = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);
        
        $ticket->expects($this->once())
               ->method('getAssignedTo')
               ->willReturn($otherUser);

        $ticketService->closeTicket($ticket, $currentUser);
    }

    // ===== QUERY TICKETS TESTS =====

    public function testTicketServiceHasGetTicketsByOwnerMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('getTicketsByOwner'), 'TicketService should have a getTicketsByOwner method');
    }

    public function testGetTicketsByOwnerReturnsArray(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\User')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $owner = $this->createMock(User::class);

        $tickets = $ticketService->getTicketsByOwner($owner);
        $this->assertIsArray($tickets);
    }

    public function testTicketServiceHasGetTicketsByAssigneeMethod(): void
    {
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }

        $serviceReflector = new \ReflectionClass('App\Service\TicketService');
        $this->assertTrue($serviceReflector->hasMethod('getTicketsByAssignee'), 'TicketService should have a getTicketsByAssignee method');
    }

    public function testGetTicketsByAssigneeReturnsArray(): void
    {
        if (!class_exists('App\Service\TicketService') || !class_exists('App\Entity\User')) {
            $this->markTestSkipped('Required classes do not exist yet - implement them first');
        }

        $ticketService = new TicketService();
        $assignee = $this->createMock(User::class);

        $tickets = $ticketService->getTicketsByAssignee($assignee);
        $this->assertIsArray($tickets);
    }
}
