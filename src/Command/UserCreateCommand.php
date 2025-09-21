<?php
// src/Command/UserCreateCommand.php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crée un utilisateur (rôle par défaut: ROLE_ADMIN)'
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de connexion')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Nom d’affichage (username)')
            ->addOption('agence', null, InputOption::VALUE_REQUIRED, 'Code agence')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Rôle principal (ex: ROLE_ADMIN, ROLE_USER)', 'ROLE_ADMIN')
            ->addOption('update-if-exists', null, InputOption::VALUE_NONE, 'Mettre à jour le mot de passe/infos si l’email existe déjà');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $help = 'Tapez le mot de passe; il ne s’affichera pas.';
        $email = (string) $input->getArgument('email');
        $role  = strtoupper((string) $input->getOption('role'));
        $username = $input->getOption('username');
        $agence   = $input->getOption('agence');
        $updateIfExists = (bool) $input->getOption('update-if-exists');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Email invalide.');
            return Command::INVALID;
        }
        if (!preg_match('/^ROLE_[A-Z_]+$/', $role)) {
            $io->error('Le rôle doit ressembler à ROLE_XYZ (ex: ROLE_ADMIN).');
            return Command::INVALID;
        }

        // Questions interactives si options manquantes
        $helper = $this->getHelper('question');

        if (!$username) {
            $q = new Question('Username (affichage) : ');
            $username = (string) $helper->ask($input, $output, $q);
        }

        if (!$agence) {
            $q = new Question('Code agence (laisser vide si non applicable) : ', '');
            $agence = (string) $helper->ask($input, $output, $q);
        }

        $pwdQ = (new Question('Mot de passe (caché) : '))
            ->setHidden(true)->setHiddenFallback(false);
        $plain = (string) $helper->ask($input, $output, $pwdQ);
        if ($plain === '') {
            $io->error('Mot de passe vide.');
            return Command::INVALID;
        }

        $existing = $this->users->findOneBy(['email' => $email]);

        if ($existing) {
            if (!$updateIfExists) {
                $io->warning("Un utilisateur avec l’email {$email} existe déjà.");
                $confirm = new ConfirmationQuestion('Mettre à jour son mot de passe et ses infos ? (y/N) ', false);
                if (!$helper->ask($input, $output, $confirm)) {
                    return Command::FAILURE;
                }
            }
            $user = $existing;
            $io->note('Mise à jour de l’utilisateur existant…');
        } else {
            $user = new User();
        }

        $user->setEmail($email);
        $user->setUsername($username ?: $email);
        if ($agence !== '') {
            $user->setAgence($agence);
        }

        // Rôles : on force le rôle choisi (ROLE_ADMIN par défaut).
        $user->setRoles([$role]);

        // Hash du mot de passe
        $hash = $this->hasher->hashPassword($user, $plain);
        $user->setPassword($hash);

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf(
            "Utilisateur %s %s avec rôle %s.",
            $email,
            $existing ? 'mis à jour' : 'créé',
            $role
        ));

        return Command::SUCCESS;
    }
}
