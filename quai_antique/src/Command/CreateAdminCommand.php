<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create:admin',
    description: 'Creates an admin user for the application',
)]
class CreateAdminCommand extends Command
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // First ensure the User table is properly set up
        $io->note('Ensuring User table structure is correct...');
        
        try {
            $userTableCommand = $this->getApplication()->find('app:create:user-table');
            $userTableInput = new ArrayInput([]);
            $userTableCommand->run($userTableInput, $output);
        } catch (\Exception $e) {
            $io->error('Failed to repair User table: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        // Get or prompt for email
        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Email for admin user', 'admin@example.com');
        }
        
        // Check if user already exists
        try {
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($existingUser) {
                // If user exists but is not an admin, make them admin
                if (!in_array('ROLE_ADMIN', $existingUser->getRoles())) {
                    $existingUser->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
                    $this->entityManager->flush();
                    $io->success("User $email updated with ROLE_ADMIN");
                    
                    $io->note('Access the admin panel at: http://localhost:8000/admin');
                    $io->note('Log in with your email and password');
                    
                    return Command::SUCCESS;
                }
                
                $io->warning("User $email already exists and already has admin rights");
                $io->note('Access the admin panel at: http://localhost:8000/admin');
                
                return Command::SUCCESS;
            }
        } catch (\Exception $e) {
            $io->warning("Error checking for existing user: " . $e->getMessage());
            $io->warning("Will attempt to create new user anyway.");
        }
        
        // Get or prompt for password
        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askHidden('Password for admin user (input is hidden)');
        }
        
        // Create new admin user
        try {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $io->success("Admin user created with email: $email");
            $io->note('Access the admin panel at: http://localhost:8000/admin');
            $io->note('Log in with your email and password');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to create admin user: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
