<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Créer un utilisateur'
)]
final class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            // options optionnelles : si manquantes, on posera les questions
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Nom d’utilisateur')
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe en clair')
            ->addOption('agence', null, InputOption::VALUE_OPTIONAL, 'Agence (code ou libellé)')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Attribuer ROLE_ADMIN');
    }

    /** @return int<0,255> */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        \assert($helper instanceof QuestionHelper);

        $username = $this->optString($input, 'username')
            ?? $this->askString($helper, $input, $output, 'Nom d’utilisateur : ');
        $email = $this->optString($input, 'email')
            ?? $this->askString($helper, $input, $output, 'Email : ');
        $plainPassword = $this->optString($input, 'password')
            ?? $this->askHiddenString($helper, $input, $output, 'Mot de passe : ');
        $agence = $this->optString($input, 'agence')
            ?? $this->askString($helper, $input, $output, 'Agence : ');

        // Validations minimales (tapées)
        if ($username === '' || $email === '' || $plainPassword === '' || $agence === '') {
            $output->writeln('<error>username, email, password et agence sont requis.</error>');
            return Command::INVALID;
        }
        if (false === \filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Email invalide.</error>');
            return Command::INVALID;
        }

        $isAdmin = $this->optBool($input, 'admin');

        $user = new User();
        $user->setUsername($username);          // string garanti
        $user->setEmail($email);                // string garanti
        $user->setAgence($agence);              // string garanti

        $roles = ['ROLE_USER'];
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        // si ton User impose des rôles uniques :
        $roles = \array_values(\array_unique($roles));
        $user->setRoles($roles);

        $hash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hash);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(\sprintf('<info>Utilisateur créé :</info> %s (%s) [%s]', $username, $email, \implode(',', $roles)));

        return Command::SUCCESS;
    }

    /** Option -> string|null (garantit le type pour PHPStan) */
    private function optString(InputInterface $input, string $name): ?string
    {
        $v = $input->getOption($name);
        if (\is_string($v)) {
            $v = \trim($v);
            return $v !== '' ? $v : null;
        }
        return null;
    }

    /** Option booléenne VALUE_NONE */
    private function optBool(InputInterface $input, string $name): bool
    {
        $v = $input->getOption($name);
        return \is_bool($v) ? $v : false;
    }

    /** Pose une question et renvoie une string non nulle (sinon relance) */
    private function askString(QuestionHelper $helper, InputInterface $input, OutputInterface $output, string $label): string
    {
        $q = new Question($label);
        $q->setValidator(static function ($answer): string {
            if (!\is_string($answer) || \trim($answer) === '') {
                throw new \RuntimeException('Valeur requise.');
            }
            return \trim($answer);
        });
        /** @var string $value */
        $value = $helper->ask($input, $output, $q);
        return $value;
    }

    /** Question masquée pour le mot de passe */
    private function askHiddenString(QuestionHelper $helper, InputInterface $input, OutputInterface $output, string $label): string
    {
        $q = new Question($label);
        $q->setHidden(true);
        $q->setHiddenFallback(false);
        $q->setValidator(static function ($answer): string {
            if (!\is_string($answer) || \trim($answer) === '') {
                throw new \RuntimeException('Mot de passe requis.');
            }
            return \trim($answer);
        });
        /** @var string $value */
        $value = $helper->ask($input, $output, $q);
        return $value;
    }
}
