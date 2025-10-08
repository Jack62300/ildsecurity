<?php
// src/EventSubscriber/DoctrineAuditSubscriber.php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Security\Auditable;
use App\Security\AuditableEntity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Proxy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class DoctrineAuditSubscriber implements EventSubscriber
{
    /** @var list<array{action:string, entity:object, type:string, id:?string, payload:?array, ctx:array}> */
    private array $queue = [];
    private bool $flushing = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokens,
        private readonly string $ipPepper, // %env(APP_IP_PEPPER)%
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postFlush,
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $em     = $args->getObjectManager();
        $entity = $args->getObject();

        if (!$this->isAuditable($entity)) { return; }

        $meta  = $em->getClassMetadata($this->realClass($entity));
        $ctx   = $this->buildContext();
        $data  = $this->snapshotScalars($meta, $entity); // optional: état initial

        $this->queue[] = [
            'action'  => 'create',
            'entity'  => $entity,
            'type'    => $entity::class,
            'id'      => null, // l’ID auto sera connu en postFlush
            'payload' => ['created' => $data],
            'ctx'     => $ctx,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $em     = $args->getObjectManager();
        $entity = $args->getObject();

        if (!$this->isAuditable($entity)) { return; }

        $changes = $args->getEntityChangeSet();
        if (!$changes) { return; }

        $meta  = $em->getClassMetadata($this->realClass($entity));
        $ctx   = $this->buildContext();
        $diff  = $this->changesOldNew($changes);

        $this->queue[] = [
            'action'  => 'update',
            'entity'  => $entity,
            'type'    => $entity::class,
            'id'      => $this->stringifyId($meta->getIdentifierValues($entity)) ?: null,
            'payload' => ['changes' => $diff],
            'ctx'     => $ctx,
        ];
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $em     = $args->getObjectManager();
        $entity = $args->getObject();

        if (!$this->isAuditable($entity)) { return; }

        $meta = $em->getClassMetadata($this->realClass($entity));
        $ctx  = $this->buildContext();

        // ⚠️ on “fige” l’ID ici, car après flush l’entité sera détachée/supprimée
        $idStr = $this->stringifyId($meta->getIdentifierValues($entity)) ?: null;

        $this->queue[] = [
            'action'  => 'delete',
            'entity'  => $entity,
            'type'    => $entity::class,
            'id'      => $idStr,
            'payload' => null,
            'ctx'     => $ctx,
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->flushing || !$this->queue) { return; }
        $this->flushing = true;

        /** @var EntityManagerInterface $em */
        $em = $args->getObjectManager();

        while ($evt = array_shift($this->queue)) {
            $entity = $evt['entity'];
            $type   = $evt['type'];
            $id     = $evt['id'];

            // Pour les create, récupère maintenant l’ID généré
            if ($evt['action'] === 'create' && !$id) {
                $meta  = $em->getClassMetadata($this->realClass($entity));
                $id    = $this->stringifyId($meta->getIdentifierValues($entity)) ?: null;
            }

            $log = new AuditLog();
            $log->setAction($evt['action']);
            $log->setObjectType($type);
            $log->setObjectId($id);
            $log->setPayload($evt['payload']);
            // contexte
            $log->setUserIdentifier($evt['ctx']['userIdentifier']);
            $log->setMethod($evt['ctx']['method']);
            $log->setRoute($evt['ctx']['route']);
            $log->setPath($evt['ctx']['path']);
            $log->setUserAgent($evt['ctx']['userAgent']);
            $log->setIpHash($evt['ctx']['ipHash']);

            $em->persist($log);
        }

        $em->flush(); // flush des AuditLog
        $this->flushing = false;
    }

    // ---------- helpers ----------

    private function isAuditable(object $entity): bool
    {
        // 1) marqueur interface (le plus fiable, passe à travers proxys)
        if ($entity instanceof AuditableEntity) {
            return true;
        }

        // 2) attribut #[Auditable] sur la classe réelle
        $ref = new \ReflectionClass($this->realClass($entity));
        return !empty($ref->getAttributes(Auditable::class));
    }

    private function realClass(object $entity): string
    {
        return $entity instanceof Proxy ? get_parent_class($entity) ?: $entity::class : $entity::class;
    }

    private function stringifyId(array $ids): string
    {
        if (!$ids) return '';
        return implode(':', array_map(static fn($k,$v) => "$k=$v", array_keys($ids), array_values($ids)));
    }

    private function snapshotScalars($meta, object $entity): array
    {
        $out = [];
        foreach ($meta->getFieldNames() as $field) {
            $out[$field] = $this->simplify($meta->getFieldValue($entity, $field));
        }
        return $out;
    }

    private function changesOldNew(array $changes): array
    {
        $out = [];
        foreach ($changes as $field => [$old, $new]) {
            $out[$field] = [
                'old' => $this->simplify($old),
                'new' => $this->simplify($new),
            ];
        }
        foreach (['password','plainPassword','current_password','token','_csrf_token','secret','client_secret'] as $k) {
            if (isset($out[$k])) { $out[$k] = ['old'=>'[REDACTED]','new'=>'[REDACTED]']; }
        }
        return $out;
    }

    private function simplify(mixed $v): mixed
    {
        if ($v instanceof \DateTimeInterface) return $v->format('c');
        if (is_object($v)) {
            return method_exists($v, 'getId')
                ? ['_class' => $this->realClass($v), 'id' => $v->getId()]
                : ['_class' => $this->realClass($v)];
        }
        if (is_array($v)) return json_decode(json_encode($v), true);
        return $v;
    }

    private function buildContext(): array
    {
        $req  = $this->requestStack->getCurrentRequest();
        $user = $this->tokens->getToken()?->getUser();
        $uid  = \is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user;

        $ip     = $req?->getClientIp() ?? '';
        $ipHash = $ip ? rtrim(strtr(base64_encode(hash_hmac('sha256', $ip, $this->ipPepper, true)), '+/', '-_'), '=') : null;

        return [
            'userIdentifier' => $uid ?: null,
            'method'         => $req?->getMethod() ?? 'CLI',
            'route'          => $req?->attributes->get('_route'),
            'path'           => $req?->getRequestUri(),
            'userAgent'      => $req?->headers->get('User-Agent'),
            'ipHash'         => $ipHash,
        ];
    }
}
