<?php

namespace App\Controller;

use ApiPlatform\Api\IriConverterInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(IriConverterInterface $iriConverter, #[CurrentUser] User $user = null): Response
    {
        if (!$user) {
            return $this->json([
                'error' => 'Invalid login request: Make sure that the Content-Type header is set to "Application/json"',
            ], 401);
        }

        // 204 means it was successful, but there is no content to return
        return new Response(null, 204, [
            'Location' => $iriConverter->getIriFromResource($user),
        ]);
    }

    /**
     * Built-in security system is listening to this route which is configured in security.yaml
     * It handles logout and redirect automatically, thus the method body won't be reached.
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {        
        throw new \Exception('This should never be reached');
    }
}