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

        // Simule la suppression du ticket
        $ticket = null;

        // Suppression des commentaires liés à un ticket supprimé
        $comments = array_filter($comments, function ($comment) {
            return $comment->getTicket() !== null;
        });

        $this->assertCount(0, $comments, 'Tous les commentaires liés au ticket supprimé doivent être supprimés');
    }

    public function testCascadeDeleteWithUser(): void
    {
        $user = new User();
        $user->setEmail('julien@example.com');

        $comment1 = new Comment();
        $comment1->setAuthor($user);
        $comment1->setContent('Commentaire A');

        $comment2 = new Comment();
        $comment2->setAuthor($user);
        $comment2->setContent('Commentaire B');

        $anotherUser = new User();
        $anotherUser->setEmail('autre@example.com');

        $comment3 = new Comment();
        $comment3->setAuthor($anotherUser);
        $comment3->setContent('Commentaire C');

        $comments = [$comment1, $comment2, $comment3];

        // Simule suppression de l'utilisateur
        $user = null;

        // Suppression des commentaires de l'utilisateur supprimé
        $comments = array_filter($comments, function ($comment) {
            return $comment->getAuthor() !== null;
        });

        $this->assertCount(1, $comments, 'Seul le commentaire de l’autre utilisateur doit rester');
    }
}
