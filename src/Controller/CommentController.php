<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Repository\TicketRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api', name: 'api_')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/tickets/{id}/comments', name: 'add_comment', methods: ['POST'])]
    public function addComment(int $id, Request $request, TicketRepository $ticketRepo): JsonResponse
    {
        $ticket = $ticketRepo->find($id);
        if (!$ticket) {
            return $this->json(['error' => 'Ticket non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['content']) || empty(trim($data['content']))) {
            return $this->json(['error' => 'Le contenu du commentaire est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser(); // ✅ getUser() via AbstractController

        // ✅ Seuls le propriétaire ou l’assigné peuvent commenter
        if ($ticket->getOwner() !== $user && $ticket->getAssignedTo() !== $user) {
            return $this->json(['error' => 'Vous n\'avez pas le droit d\'ajouter un commentaire sur ce ticket.'], Response::HTTP_FORBIDDEN);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setAuthor($user);
        $comment->setTicket($ticket);

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
            'author' => $user->getUserIdentifier(), // getEmail() si ton User l’implémente
        ], Response::HTTP_CREATED);
    }

    #[Route('/comments/{id}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(int $id, CommentRepository $commentRepo): JsonResponse
    {
        $comment = $commentRepo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // ✅ Seul l’auteur peut supprimer son commentaire
        if ($comment->getAuthor() !== $user) {
            return $this->json(['error' => 'Vous n\'êtes pas l\'auteur de ce commentaire.'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
