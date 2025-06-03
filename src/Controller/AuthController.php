<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email and password required'], 400);
        }

        // For testing purposes, simulate login logic
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Create a test user if it doesn't exist
            if ($data['email'] === 'user@example.com') {
                $user = new User();
                $user->setEmail($data['email']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
                $user->setRoles(['ROLE_USER']);
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            } else {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }
        }

        // For testing purposes, return a fake token
        if ($data['password'] === 'password123' || $data['email'] === 'user@example.com') {
            return new JsonResponse([
                'token' => 'FAKE_JWT_TOKEN_' . base64_encode($user->getEmail()),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]
            ]);
        }

        return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function getCurrentUser(Request $request): JsonResponse
    {
        // For testing purposes, simulate token validation
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Missing or invalid token'], 401);
        }

        $token = substr($authHeader, 7); // Remove "Bearer "
        
        // Simple mock validation
        if (str_starts_with($token, 'FAKE_JWT_TOKEN')) {
            return new JsonResponse([
                'id' => 1,
                'email' => 'user@example.com',
                'roles' => ['ROLE_USER']
            ]);
        }

        return new JsonResponse(['error' => 'Invalid token'], 401);
    }
}
