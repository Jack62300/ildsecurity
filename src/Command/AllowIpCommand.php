<?php
// src/Command/AllowIpCommand.php
namespace App\Command;

use App\Entity\AllowedNetwork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:allow-ip', description: 'Ajoute une IP ou un réseau CIDR à la liste blanche')]
class AllowIpCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('cidr_or_ip', InputArgument::REQUIRED, 'Ex: 203.0.113.42 ou 203.0.113.0/24 ou 2001:db8::/32')
             ->addArgument('label', InputArgument::OPTIONAL, 'Label facultatif');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $value = (string)$input->getArgument('cidr_or_ip');
        $label = $input->getArgument('label');

        $network = $value;
        $prefix = null;

        if (str_contains($value, '/')) {
            [$network, $prefixStr] = explode('/', $value, 2);
            $prefix = ctype_digit($prefixStr) ? (int)$prefixStr : null;
        }

        if (!filter_var($network, FILTER_VALIDATE_IP)) {
            $io->error('Adresse IP invalide.');
            return Command::INVALID;
        }
        if ($prefix !== null && ($prefix < 0 || $prefix > (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32))) {
            $io->error('Préfixe CIDR invalide.');
            return Command::INVALID;
        }

        $entry = new AllowedNetwork();
        $entry->setNetwork($network);
        $entry->setPrefixLength($prefix);
        $entry->setLabel($label);

        $this->em->persist($entry);
        $this->em->flush();

        $io->success(sprintf('Ajouté: %s%s', $network, $prefix !== null ? "/$prefix" : ''));
        return Command::SUCCESS;
    }
}
