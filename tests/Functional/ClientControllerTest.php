<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Tests fonctionnels basiques sur ClientController :
 * - accès index (protégé) avec login
 * - export CSV : headers + BOM + en-têtes
 */
final class ClientControllerTest extends WebTestCase
{
    private const FIREWALL = 'main';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        // Recrée le schéma pour chaque test (via DatabaseTestCase, mais ici on est en WebTestCase)
        $kernel = static::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $tool->dropDatabase();
        $metas = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metas)) {
            $tool->createSchema($metas);
        }

        $this->client = static::createClient();
    }

    private function loginAs(array $roles): void
    {
        // Utilise un InMemoryUser pour bypass le provider
        $user = new InMemoryUser('tester@example.org', null, $roles);
        $this->client->loginUser($user, self::FIREWALL);
    }

    public function testIndexAccessibleWhenLoggedIn(): void
    {
        $this->loginAs(['ROLE_USER']);
        $this->client->request('GET', '/clients');

        $this->assertSame(
            200,
            $this->client->getResponse()->getStatusCode(),
            'L’index clients doit répondre 200 pour ROLE_USER.'
        );
    }

    public function testExportCsvHeadersAndContent(): void
    {
        $this->loginAs(['ROLE_USER']);

        // Pas besoin d’insérer de clients : l’export doit toujours produire au moins l’entête
        $this->client->request('GET', '/clients/export');

        $resp = $this->client->getResponse();
        $this->assertSame(200, $resp->getStatusCode(), 'Export CSV doit répondre 200.');
        $this->assertTrue(
            str_contains($resp->headers->get('content-type') ?? '', 'text/csv'),
            'Content-Type doit être text/csv.'
        );
        $this->assertTrue(
            (bool)preg_match('/attachment;.*\.csv"/', $resp->headers->get('content-disposition') ?? ''),
            'Content-Disposition doit forcer le téléchargement .csv.'
        );

        $content = $resp->getContent() ?? '';
        // Doit commencer par BOM UTF-8 + une ligne d’en-têtes (ID;Nom;…)
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'Le CSV doit commencer par un BOM UTF-8 (Excel).');
        $this->assertStringContainsString("ID;Nom;", $content, 'La première ligne doit contenir les entêtes ID;Nom;...');
    }
}
