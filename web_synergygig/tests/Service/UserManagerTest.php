<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser(): void
    {
        $user = new User();
        $user->setFirstName('Mohamed');
        $user->setLastName('Seji');
        $user->setEmail('mohamed.seji@synergygig.com');
        $user->setPassword('SecurePass123');

        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutFirstName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire');

        $user = new User();
        $user->setLastName('Seji');
        $user->setEmail('test@synergygig.com');
        $user->setPassword('SecurePass123');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithoutLastName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setFirstName('Mohamed');
        $user->setEmail('test@synergygig.com');
        $user->setPassword('SecurePass123');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est invalide');

        $user = new User();
        $user->setFirstName('Mohamed');
        $user->setLastName('Seji');
        $user->setEmail('email_invalide');
        $user->setPassword('SecurePass123');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères');

        $user = new User();
        $user->setFirstName('Mohamed');
        $user->setLastName('Seji');
        $user->setEmail('test@synergygig.com');
        $user->setPassword('abc');

        $manager = new UserManager();
        $manager->validate($user);
    }
}
