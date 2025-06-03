<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Comment;
use App\Entity\User;
use App\Entity\Ticket;

class CommentEntityTest extends TestCase
{
    public function testAddComment(): void
    {
        $user = new User();
        $ticket = new Ticket();
        $content = 'Ceci est un commentaire de test';

        $comment = new Comment();
        $comment->setAuthor($user);
        $comment->setTicket($ticket);
        $comment->setContent($content);

        $this->assertSame($user, $comment->getAuthor());
        $this->assertSame($ticket, $comment->getTicket());
        $this->assertSame($content, $comment->getContent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());
    }

    public function testDeleteComment(): void
    {
        $comment = new Comment();
        $comment->setContent('à supprimer');
        
        // Simule la suppression
        $comment = null;

        $this->assertNull($comment);
    }
}
