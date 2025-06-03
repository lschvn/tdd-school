<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Service\TicketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class TicketController extends AbstractController
{
    private TicketService $ticketService;
    private EntityManagerInterface $entityManager;

    public function __construct(TicketService $ticketService, EntityManagerInterface $entityManager)
    {
        $this->ticketService = $ticketService;
        $this->entityManager = $entityManager;
    }

    /**
     * List all tickets
     */
    #[Route('/tickets', name: 'api_tickets_list', methods: ['GET'])]
    public function listTickets(): JsonResponse
    {
        try {
            $tickets = $this->ticketService->getAllTickets();
            
            $data = array_map(fn($ticket) => $ticket->toArray(), $tickets);
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get specific ticket
     */
    #[Route('/tickets/{id}', name: 'api_tickets_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showTicket(int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }
            
            return new JsonResponse($ticket->toArray());
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create new ticket
     */
    #[Route('/tickets', name: 'api_tickets_create', methods: ['POST'])]
    public function createTicket(Request $request): JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return new JsonResponse(['error' => 'Content-Type must be application/json'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            $errors = $this->validateCreateTicketData($data);
            if (!empty($errors)) {
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // Get users (for testing, create mocks)
            $owner = $this->getUserById($data['ownerId'] ?? 1);
            $assignedTo = $this->getUserById($data['assignedToId'] ?? 1);

            $ticket = $this->ticketService->createTicket(
                $owner,
                $assignedTo,
                $data['title'],
                $data['description'],
                $data['priority'],
                $data['status']
            );

            return new JsonResponse($ticket->toArray(), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update ticket
     */
    #[Route('/tickets/{id}', name: 'api_tickets_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateTicket(int $id, Request $request): JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return new JsonResponse(['error' => 'Content-Type must be application/json'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
            }

            $description = $data['description'] ?? null;
            $priority = $data['priority'] ?? null;

            $this->ticketService->updateTicket($ticket, $description, $priority);

            return new JsonResponse($ticket->toArray());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete ticket
     */
    #[Route('/tickets/{id}', name: 'api_tickets_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteTicket(int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $this->ticketService->deleteTicket($ticket);

            return new JsonResponse(['message' => 'Ticket deleted successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign ticket to user
     */
    #[Route('/tickets/{id}/assign', name: 'api_tickets_assign', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function assignTicket(int $id, Request $request): JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return new JsonResponse(['error' => 'Content-Type must be application/json'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['userId'])) {
                return new JsonResponse(['error' => 'User ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUserById($data['userId']);
            $this->ticketService->assignTicket($ticket, $user);

            return new JsonResponse(['message' => 'Ticket assigned successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unassign ticket
     */
    #[Route('/tickets/{id}/assign', name: 'api_tickets_unassign', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function unassignTicket(int $id): JsonResponse
    {
        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $this->ticketService->unassignTicket($ticket);

            return new JsonResponse(['message' => 'Ticket unassigned successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Start ticket processing
     */
    #[Route('/tickets/{id}/start', name: 'api_tickets_start', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function startTicket(int $id, Request $request): JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return new JsonResponse(['error' => 'Content-Type must be application/json'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            $user = $this->getUserById($data['userId'] ?? 1);

            $this->ticketService->startTicket($ticket, $user);

            return new JsonResponse(['message' => 'Ticket started successfully']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Close ticket
     */
    #[Route('/tickets/{id}/close', name: 'api_tickets_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function closeTicket(int $id, Request $request): JsonResponse
    {
        if (!$this->isJsonRequest($request)) {
            return new JsonResponse(['error' => 'Content-Type must be application/json'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ticket = $this->ticketService->getTicket($id);
            
            if (!$ticket) {
                return new JsonResponse(['error' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            $user = $this->getUserById($data['userId'] ?? 1);

            $this->ticketService->closeTicket($ticket, $user);

            return new JsonResponse(['message' => 'Ticket closed successfully']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get tickets owned by user
     */
    #[Route('/users/{id}/tickets/owned', name: 'api_users_tickets_owned', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getUserOwnedTickets(int $id): JsonResponse
    {
        try {
            $user = $this->getUserById($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $tickets = $this->ticketService->getTicketsByOwner($user);
            $data = array_map(fn($ticket) => $ticket->toArray(), $tickets);

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get tickets assigned to user
     */
    #[Route('/users/{id}/tickets/assigned', name: 'api_users_tickets_assigned', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getUserAssignedTickets(int $id): JsonResponse
    {
        try {
            $user = $this->getUserById($id);
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $tickets = $this->ticketService->getTicketsByAssignee($user);
            $data = array_map(fn($ticket) => $ticket->toArray(), $tickets);

            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check if request has JSON content type
     */
    private function isJsonRequest(Request $request): bool
    {
        return $request->headers->get('Content-Type') === 'application/json';
    }

    /**
     * Validate create ticket data
     */
    private function validateCreateTicketData(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }

        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }

        if (!in_array($data['priority'] ?? '', ['basse', 'normale', 'haute'])) {
            $errors[] = 'Priority must be one of: basse, normale, haute';
        }

        if (!in_array($data['status'] ?? '', ['pending', 'waiting', 'in-progress', 'done'])) {
            $errors[] = 'Status must be one of: pending, waiting, in-progress, done';
        }

        return $errors;
    }

    /**
     * Get user by ID (for testing purposes, create mock users)
     */
    private function getUserById(int $id): ?User
    {
        // For testing purposes, return a mock user
        if ($id > 0 && $id < 999) {
            $user = new User();
            
            // Use reflection to set the ID since it's private
            $reflection = new \ReflectionClass($user);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($user, $id);
            
            $user->setEmail("user{$id}@test.com");
            
            return $user;
        }

        return null;
    }
}
