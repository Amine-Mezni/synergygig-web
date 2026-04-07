<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-existing-users-passwords',
    description: 'Hash les mots de passe existants des utilisateurs',
)]
class HashExistingUsersPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $usersData = [
            'admin@sg.com' => 'anasadmin123',
            'hr@sg.com' => 'anashr123',
            'emp@sg.com' => 'anasemp123',
            'gig@sg.com' => 'anasgig123',
        ];

        foreach ($usersData as $email => $plainPassword) {
            $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $output->writeln("Utilisateur introuvable : $email");
                continue;
            }

            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $output->writeln("Mot de passe hashé pour : $email");
        }

        $this->entityManager->flush();

        $output->writeln('Tous les mots de passe ont été mis à jour.');

        return Command::SUCCESS;
    }
}