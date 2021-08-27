<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $baseUser = new User();
        $baseUser->setEmail('admin@email.com');
        $baseUser->setUsername('admin');
        $baseUser->setFirstName('Base');
        $baseUser->setLastName('User');
        $baseUser->setPassword($this->passwordHasher->hashPassword($baseUser, '123'));

        $manager->persist($baseUser);

        $manager->flush();
    }
}
