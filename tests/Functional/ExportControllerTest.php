<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Doctrine\ORM\Tools\SchemaTool;

final class ExportControllerTest extends WebTestCase
{
    private const FIREWALL = 'main';

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();
        $tool = new SchemaTool($em);
        $tool->dropDatabase();
        $metas = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metas)) {
            $tool->createSchema($metas);
        }
    }

    public function testClientsCsv(): void
    {
        $client = static::createClient();
        $user = new InMemoryUser('tester', null, ['ROLE_USER']);
        $client->loginUser($user, self::FIREWALL);

        // ⚠️ adapte l’URL si différente
        $client->request('GET', '/clients/export');

        $resp = $client->getResponse();
        $this->assertSame(200, $resp->getStatusCode());
        $this->assertStringContainsString('text/csv', (string)$resp->headers->get('content-type'));
        $this->assertStringContainsString("\xEF\xBB\xBF", $resp->getContent() ?? '');
        $this->assertStringContainsString("ID;Nom;", $resp->getContent() ?? '');
    }
}
