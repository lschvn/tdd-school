<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\User;

class UserEntityTest extends TestCase
{
    public function testUserEntityExists(): void
    {
        $this->assertTrue(class_exists('App\Entity\User'), 'User entity does not exist.');
    }

    public function testUserEntityCanBeInstantiated(): void
    {
        if (!class_exists('App\Entity\User')) {
            $this->markTestSkipped('User entity does not exist yet - implement it first');
        }

        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }
}
