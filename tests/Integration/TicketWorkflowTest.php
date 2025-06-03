<?php

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Service\TicketService;
use App\Entity\Ticket;
use App\Entity\User;

class TicketWorkflowTest extends TestCase
{
    private TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!class_exists('App\Service\TicketService')) {
            $this->markTestSkipped('TicketService class does not exist yet - implement it first');
        }
        
        $this->ticketService = new TicketService();
    }

    public function testCompleteTicketCreationWorkflow(): void
    {
        if (!class_exists('App\Entity\Ticket') || !class_exists('App\Entity\User')) {
            $this->markTestSkipped('Required entities do not exist yet - implement them first');
        }

        // Arrange
        $owner = $this->createMock(User::class);
        $assignedTo = $this->createMock(User::class);
        
        // Act
        $ticket = $this->ticketService->createTicket(
            $owner,
            $assignedTo,
            'Bug: Application crashes on login',
            'When user tries to login with valid credentials, the application crashes unexpectedly.',
            'haute',
            'pending'
        );

        // Assert
        $this->assertInstanceOf(Ticket::class, $ticket);
        
        // Verify all properties are correctly set
        $this->assertSame($owner, $ticket->getOwner());
        $this->assertSame($assignedTo, $ticket->getAssignedTo());
        $this->assertSame('Bug: Application crashes on login', $ticket->getTitle());
        $this->assertSame('When user tries to login with valid credentials, the application crashes unexpectedly.', $ticket->getDescription());
        $this->assertSame('haute', $ticket->getPriority());
        $this->assertSame('pending', $ticket->getStatus());
        
        // Verify timestamps are properly set
        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getFirstAssignedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $ticket->getLastAssignedAt());
        
        // Verify timestamps logical consistency
        $this->assertLessThanOrEqual($ticket->getFirstAssignedAt(), $ticket->getCreatedAt());
        $this->assertEquals($ticket->getFirstAssignedAt(), $ticket->getLastAssignedAt());
    }

    public function testTicketCreationWithAllPriorities(): void
    {
        if (!class_exists('App\Entity\Ticket') || !class_exists('App\Entity\User')) {
            $this->markTestSkipped('Required entities do not exist yet - implement them first');
        }

        $owner = $this->createMock(User::class);
        $assignedTo = $this->createMock(User::class);
        
        $priorities = ['basse', 'normale', 'haute'];
        
        foreach ($priorities as $priority) {
            $ticket = $this->ticketService->createTicket(
                $owner,
                $assignedTo,
                "Ticket with priority: $priority",
                'Test description',
                $priority,
                'pending'
            );
            
            $this->assertSame($priority, $ticket->getPriority());
        }
    }

    public function testTicketCreationWithAllStatuses(): void
    {
        if (!class_exists('App\Entity\Ticket') || !class_exists('App\Entity\User')) {
            $this->markTestSkipped('Required entities do not exist yet - implement them first');
        }

        $owner = $this->createMock(User::class);
        $assignedTo = $this->createMock(User::class);
        
        $statuses = ['pending', 'waiting', 'in-progress', 'done'];
        
        foreach ($statuses as $status) {
            $ticket = $this->ticketService->createTicket(
                $owner,
                $assignedTo,
                "Ticket with status: $status",
                'Test description',
                'normale',
                $status
            );
            
            $this->assertSame($status, $ticket->getStatus());
        }
    }
}
