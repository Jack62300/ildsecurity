<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private RouterInterface $router) {}

    public function handle(Request $request, AccessDeniedException $exception): ?Response
    {
        // Selon ton besoin, tu peux tester ici si l'utilisateur est connecté ou non.
        // Redirection systématique vers la page de login :
        return new RedirectResponse($this->router->generate('app_login'));
    }
}

