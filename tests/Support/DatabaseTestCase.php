<?php
declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base de tests avec Doctrine : (re)crée le schéma avant chaque test.
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $tool = new SchemaTool($this->em);
        $tool->dropDatabase();
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        if (!empty($metas)) {
            $tool->createSchema($metas);
        }
    }

    protected function tearDown(): void
    {
        $this->em->close();
        unset($this->em);
        parent::tearDown();
    }
}

