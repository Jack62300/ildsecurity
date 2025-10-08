<?php
// src/Command/PurgeAuditLogsCommand.php
namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:audit:purge', description: 'Purge les logs plus anciens que X jours')]
class PurgeAuditLogsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('days', InputArgument::OPTIONAL, 'Âge en jours', 90);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getArgument('days');
        $threshold = new \DateTimeImmutable("-{$days} days");

        $qb = $this->em->createQueryBuilder()
            ->delete('App\Entity\AuditLog', 'a')
            ->andWhere('a.createdAt < :t')->setParameter('t', $threshold);

        $count = $qb->getQuery()->execute();
        $output->writeln("Supprimé: {$count} logs (< {$threshold->format('Y-m-d H:i:s')}).");
        return Command::SUCCESS;
    }
}
