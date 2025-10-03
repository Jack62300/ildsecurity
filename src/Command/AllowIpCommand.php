<?php
// src/Command/AllowIpCommand.php
namespace App\Command;

use App\Entity\AllowedNetwork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:allow-ip', description: 'Ajoute une IP ou un réseau CIDR à la liste blanche')]
class AllowIpCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // On le met en OPTIONAL pour permettre l’interactif via QuestionHelper
            ->addArgument('cidr', InputArgument::OPTIONAL, 'Ex: 203.0.113.42 ou 203.0.113.0/24 ou 2001:db8::/32')
            // label devient une option string|null
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Label facultatif');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // ---- RÉCUPÉRATION + VALIDATION DE L’ARGUMENT "cidr" ----
        $cidrArg = $input->getArgument('cidr');
        if ($cidrArg !== null && !\is_string($cidrArg)) {
            throw new \InvalidArgumentException('Argument "cidr" doit être une chaîne si fourni.');
        }

        $cidr = $cidrArg;
        if ($cidr === null || $cidr === '') {
            // Utilise QuestionHelper pour demander interactivement
            $helper = $this->getHelper('question');
            \assert($helper instanceof QuestionHelper);

            $question = new Question('CIDR ou IP (ex: 203.0.113.42, 203.0.113.0/24, 2001:db8::/32) : ');
            $question->setValidator(function ($answer) {
                if (!\is_string($answer) || $answer === '') {
                    throw new \RuntimeException('Valeur requise.');
                }
                return $answer;
            });
            $question->setMaxAttempts(3);

            $cidr = $helper->ask($input, $output, $question);
            if (!\is_string($cidr) || $cidr === '') {
                throw new \RuntimeException('Argument "cidr" invalide.');
            }
        }

        // ---- RÉCUPÉRATION + VALIDATION DE L’OPTION "--label" ----
        $label = $input->getOption('label');
        if ($label !== null && !\is_string($label)) {
            throw new \InvalidArgumentException('Option "label" doit être string|null.');
        }

        // ---- PARSING CIDR/IP ----
        $value = $cidr;
        $network = $value;
        $prefix = null;

        if (\str_contains($value, '/')) {
            [$network, $prefixStr] = \explode('/', $value, 2);
            $prefix = \ctype_digit($prefixStr) ? (int) $prefixStr : null;
        }

        if (!\filter_var($network, FILTER_VALIDATE_IP)) {
            $io->error('Adresse IP invalide.');
            return Command::INVALID;
        }

        $isIpv6 = \filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        $maxPrefix = $isIpv6 ? 128 : 32;

        if ($prefix !== null && ($prefix < 0 || $prefix > $maxPrefix)) {
            $io->error('Préfixe CIDR invalide.');
            return Command::INVALID;
        }

        // ---- PERSISTENCE ----
        $entry = new AllowedNetwork();
        $entry->setNetwork($network);
        $entry->setPrefixLength($prefix);
        $entry->setLabel($label);

        $this->em->persist($entry);
        $this->em->flush();

        $io->success(\sprintf('Ajouté : %s%s', $network, $prefix !== null ? "/$prefix" : ''));
        return Command::SUCCESS;
    }
}
