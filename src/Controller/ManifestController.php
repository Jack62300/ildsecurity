<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ManifestController extends AbstractController
{
    #[Route('/manifest.webmanifest', name: 'app_manifest', methods: ['GET'])]
    public function manifest(): Response
    {
        $data = [
            'name' => 'Mon appli',
            'short_name' => 'MonApp',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#0d9488',
            'prefer_related_applications' => false,
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ];

        return new Response(json_encode($data, JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache',
        ]);
    }

    #[Route('/app', name: 'app_shell', methods: ['GET'])]
    public function redirectToRoot(): Response
    {
        // vers la racine `/` (ta page de login)
        return $this->redirect('/');
        // ou si ta route de login s’appelle `app_login` :
        // return $this->redirectToRoute('app_login');
    }
}