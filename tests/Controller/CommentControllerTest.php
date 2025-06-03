<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Ticket;

class CommentEntityTest extends TestCase
{
    public function testCreateCommentProperties(): void
    {
        $comment = new Comment();

        $user = new User();
        $ticket = new Ticket();

        $content = 'Ceci est un commentaire de test';

        $comment->setContent($content);
        $comment->setAuthor($user);
        $comment->setTicket($ticket);

        // Vérifie que le contenu est bien enregistré
        $this->assertEquals($content, $comment->getContent());

        // Vérifie l'auteur
        $this->assertSame($user, $comment->getAuthor());

        // Vérifie l'association avec le ticket
        $this->assertSame($ticket, $comment->getTicket());

        // Vérifie que la date de création est bien un objet DateTime
        $this->assertInstanceOf(\DateTimeInterface::class, $comment->getCreatedAt());
    }

    public function testDeleteComment(): void
    {
        $comment = new Comment();

        // Simule la suppression du commentaire
        $comment->delete();

        // Vérifie que le commentaire est marqué comme supprimé
        $this->assertTrue($comment->isDeleted());
    }

}
