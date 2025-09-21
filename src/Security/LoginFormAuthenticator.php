<?php
namespace App\Security;

use App\Repository\UserRepository;
use App\Security\TrustedDeviceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UserRepository $users,
        private RouterInterface $router,
        private TrustedDeviceManager $devices,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email    = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');
        $agence   = trim((string) $request->request->get('agence', ''));
        $csrf     = (string) $request->request->get('_csrf_token', '');

        $request->getSession()->set('_security.last_username', $email);

        if ($agence === '') {
            throw new CustomUserMessageAuthenticationException('Le code agence est requis.');
        }

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $ua = $request->headers->get('User-Agent', '');

        $userBadge = new UserBadge($email, function (string $identifier) use ($agence, $ip, $ua) {
            $user = $this->users->findOneBy(['email' => $identifier]);
            if (!$user) {
                throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
            }

            $userAgence = $user->getAgence();
            $userCode = is_object($userAgence) && method_exists($userAgence, 'getCodeAgence')
                ? (string) $userAgence->getCodeAgence()
                : (string) $userAgence;

            if (mb_strtolower(trim($userCode)) !== mb_strtolower(trim($agence))) {
                throw new CustomUserMessageAuthenticationException('Code agence invalide.');
            }

            if (!$this->devices->isApproved($user, $ip)) {
                $this->devices->createOrSendPending($user, $ip, $ua);
                throw new CustomUserMessageAuthenticationException(
                    'Nouvel appareil détecté : un e-mail de validation a été envoyé.'
                );
            }

            return $user;
        });

        return new Passport(
            $userBadge,
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrf),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->router->generate('app_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }
}
