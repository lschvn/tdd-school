<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\TicketService;
use App\Entity\Ticket;
use App\Entity\User;

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
}
