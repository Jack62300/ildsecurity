<?php
declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\PhoneCategory;
use App\Entity\PhoneNumber;
use App\Tests\Support\DatabaseTestCase;

/**
 * Teste un comportement simple du repository :
 * - persistance
 * - récupération triée par nom au sein d'une catégorie
 */
final class PhoneNumberRepositoryTest extends DatabaseTestCase
{
    public function testFindByCategorySortedByName(): void
    {
        $cat = (new PhoneCategory())->setName('Astreinte');
        $this->em->persist($cat);

        $n3 = (new PhoneNumber())->setName('Zorro')->setNumber('+33 1 23 45 67 90')->setCategory($cat);
        $n1 = (new PhoneNumber())->setName('Alice')->setNumber('+33 1 23 45 67 89')->setCategory($cat);
        $n2 = (new PhoneNumber())->setName('Bob')->setNumber('+33 6 01 02 03 04')->setCategory($cat);

        foreach ([$n1, $n2, $n3] as $n) {
            $this->em->persist($n);
        }
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(PhoneNumber::class);

        // Si tu as un OrderBy sur la relation, ça ressortira déjà trié via $cat->getNumbers().
        // Ici on force le tri via findBy() pour être indépendant du mapping.
        $found = $repo->findBy(['category' => $cat /* au lieu de $cat->getId() */], ['name' => 'ASC']);

        $this->assertCount(3, $found);
        $this->assertSame('Alice', $found[0]->getName());
        $this->assertSame('Bob',   $found[1]->getName());
        $this->assertSame('Zorro', $found[2]->getName());
    }
}
