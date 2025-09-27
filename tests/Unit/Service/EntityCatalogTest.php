// tests/Unit/Service/EntityCatalogTest.php
<?php


namespace App\Tests\Unit\Service;

use App\Service\EntityCatalog;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class EntityCatalogTest extends TestCase
{
    public function testListExportablesAndDefaultSelectedConsistency(): void
    {
        $labels = [
            'App\Entity\Client'      => 'Clients',
            'App\Entity\ClientPhoto' => 'Photos clients',
            'App\Entity\Organisme'   => 'Organismes',
            'App\Entity\User'        => 'Utilisateurs',
        ];

        $em = $this->createMock(EntityManagerInterface::class); // ✅ 1er arg requis
        $catalog = new EntityCatalog($em, $labels);

        $choices = $catalog->listExportables(); // [label => FQCN]
        self::assertIsArray($choices);
        self::assertArrayHasKey('Clients', $choices);
        self::assertSame('App\Entity\Client', $choices['Clients']);

        // toutes les values doivent appartenir aux clés de $labels
        self::assertEqualsCanonicalizing(
            array_keys($labels),
            array_values($choices)
        );

        $defaults = $catalog->defaultSelected(); // array de FQCN
        self::assertIsArray($defaults);
        foreach ($defaults as $fqcn) {
            self::assertContains($fqcn, array_keys($labels));
        }
    }
}
