<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLoginWithValidCredentials(): void
    {
        // Arrange
        $client = static::createClient();
        $payload = [
            'email' => 'user@example.com',
            'password' => 'password123'
        ];

        // Act
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        // Arrange
        $client = static::createClient();
        $payload = [
            'email' => 'wrong@example.com',
            'password' => 'wrongpass'
        ];

        // Act
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCurrentUserWithValidToken(): void
    {
        // Arrange
        $client = static::createClient();

        $fakeToken = 'FAKE_JWT_TOKEN';

        // Act
        $client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ' . $fakeToken,
                'CONTENT_TYPE' => 'application/json'
            ]
        );

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $data);
    }
}
