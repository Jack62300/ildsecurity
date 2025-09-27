<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExportService
{
     public function __construct(
        private EntityManagerInterface $em,
        // ⬇️ et ici
        private NormalizerInterface $normalizer
    ) {}

    public function exportCsv(array $entitiesFqcn, string $csvDelimiter = ';', int $limit = 0): StreamedResponse
    {
        $dt = (new \DateTimeImmutable())->format('Ymd_His');
        $filename = 'export_'.$dt.'.csv';

        return new StreamedResponse(function() use ($entitiesFqcn, $csvDelimiter, $limit) {
            $out = fopen('php://output', 'wb');

            foreach ($entitiesFqcn as $fqcn) {
                // Section par entité pour faciliter l’import (GSheet/Excel gèrent très bien)
                fwrite($out, "# ". $fqcn . PHP_EOL);
                $this->streamCsv($out, $fqcn, $csvDelimiter, $limit);
                fwrite($out, PHP_EOL);
            }

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    /** @return iterable<array<string,mixed>> */
    private function fetchAsArrays(string $fqcn, int $limit = 0): iterable
    {
        $qb = $this->em->getRepository($fqcn)->createQueryBuilder('e');
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        foreach ($qb->getQuery()->toIterable() as $entity) {
            // ⬇️ utiliser $this->normalizer
            yield $this->normalizer->normalize($entity, null, [
                'circular_reference_handler' => fn($o) => method_exists($o, 'getId') ? $o->getId() : null,
            ]);
            $this->em->detach($entity);
        }
    }

    private function streamCsv($handle, string $fqcn, string $delimiter, int $limit): void
    {
        $iterator = $this->fetchAsArrays($fqcn, $limit);
        $headerDone = false;

        foreach ($iterator as $row) {
            if (!$headerDone) {
                fputcsv($handle, array_keys($row), $delimiter);
                $headerDone = true;
            }
            array_walk($row, function (&$v) {
                if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            });
            fputcsv($handle, array_values($row), $delimiter);
        }
    }
}