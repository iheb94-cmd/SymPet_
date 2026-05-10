<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck()
    {
        // Symfony handles this route
    }
}

