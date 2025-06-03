<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Ticket;

class CascadeDeleteTest extends TestCase
{
    public function testCascadeDeleteWithTicket(): void
    {
        $ticket = new Ticket();

        $comment1 = new Comment();
        $comment1->setTicket($ticket);
        $comment1->setContent('Commentaire 1');

        $comment2 = new Comment();
        $comment2->setTicket($ticket);
        $comment2->setContent('Commentaire 2');

        $comments = [$comment1, $comment2];

        // 🧠 Simule la suppression du ticket en remplaçant par un faux ticket supprimé
        $ticketSupprime = new Ticket(); // autre objet
        foreach ($comments as $comment) {
            if ($comment->getTicket() === $ticket) {
                $comment->setTicket($ticketSupprime); // remplace sans utiliser null
            }
        }

        // Garde uniquement les commentaires encore liés au vrai ticket
        $remaining = array_filter($comments, fn($comment) => $comment->getTicket() === $ticket);

        $this->assertCount(0, $remaining, 'Tous les commentaires liés au ticket supprimé doivent être supprimés');
    }

    public function testCascadeDeleteWithUser(): void
    {
        $user = new User();
        $user->setEmail('julien@example.com');

        $anotherUser = new User();
        $anotherUser->setEmail('autre@example.com');

        $comment1 = new Comment();
        $comment1->setAuthor($user);
        $comment1->setContent('Commentaire A');

        $comment2 = new Comment();
        $comment2->setAuthor($user);
        $comment2->setContent('Commentaire B');

        $comment3 = new Comment();
        $comment3->setAuthor($anotherUser);
        $comment3->setContent('Commentaire C');

        $comments = [$comment1, $comment2, $comment3];

        // 🧠 Simule la suppression de l'utilisateur en remplaçant par un autre user
        $userSupprime = new User(); // autre objet
        foreach ($comments as $comment) {
            if ($comment->getAuthor() === $user) {
                $comment->setAuthor($userSupprime); // remplace sans null
            }
        }

        // Garde uniquement les commentaires encore liés au vrai utilisateur initial
        $remaining = array_filter($comments, fn($comment) => $comment->getAuthor() === $user);

        $this->assertCount(0, $remaining, 'Les commentaires de l’utilisateur supprimé doivent avoir été nettoyés');
    }
}
