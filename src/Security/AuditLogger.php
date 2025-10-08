<?php
// src/Security/AuditLogger.php
namespace App\Security;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuditLogger
{
    private string $pepper;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokens,
        string $ipPepper // %env(APP_IP_PEPPER)%
    ) {
        $this->pepper = $ipPepper;
    }

    public function log(
        string $action,
        ?string $objectType = null,
        ?string $objectId = null,
        ?array $payload = null
    ): void {
        $req  = $this->requestStack->getCurrentRequest();
        $user = $this->tokens->getToken()?->getUser();
        $id   = \is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user;

        $ip = $req?->getClientIp() ?? '';
        $ipHash = $ip ? $this->hashIp($ip) : null;

        $log = new AuditLog();
        // setters à compléter si générés par maker
        (function() use ($log, $req, $id, $ipHash, $action, $objectType, $objectId, $payload) {
            $log->setAction($action);
            $log->setUserIdentifier($id ?: null);
            $log->setMethod($req?->getMethod() ?? 'CLI');
            $log->setRoute($req?->attributes->get('_route'));
            $log->setPath($req?->getRequestUri());
            $log->setUserAgent($req?->headers->get('User-Agent'));
            $log->setIpHash($ipHash);
            $log->setObjectType($objectType);
            $log->setObjectId($objectId);
            $log->setPayload($payload);
        })();

        $this->em->persist($log);
        // On ne flush PAS ici pour laisser la transaction courante gérer la cohérence
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    private function hashIp(string $ip): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $ip, $this->pepper, true)), '+/', '-_'), '=');
    }

    /**
     * Nettoie les données à logger (évite mdp/tokens/etc.)
     */
    public static function sanitize(array $data): array
    {
        $deny = ['password','current_password','plainPassword','_csrf_token','token','authorization','access_token','refresh_token','secret','client_secret'];
        $out = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $deny, true)) {
                $out[$k] = '[REDACTED]';
            } else {
                $out[$k] = is_scalar($v) || is_null($v) ? $v : json_decode(json_encode($v), true); // normalise
            }
        }
        return $out;
    }
}
