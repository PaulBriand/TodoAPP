<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Status;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     *
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($u = 0; $u < 5; $u++) {
            $user = new User;
            $hash = $this->encoder->hashPassword($user, "password");
            $user->setPassword($hash);

            if ($u === 0) {
                $user->setRoles(["ROLE_ADMIN"])
                    ->setEmail("admin@admin.fr");
            } else {
                $user->setEmail($faker->safeEmail());
            }

            $manager->persist($user);
        }

        $manager->flush();

        for ($c = 0; $c < 5; $c++) {

            $tag = new Tag();
            $tag->setName($faker->colorName());

            $manager->persist($tag);
        }

        $manager->flush();


        for ($s = 1; $s <= 3; $s++) {
            // Statut
            $statut = new Status;
            // Label identifiable facilement
            $statut->setLabel($s);
            // faire persister l’objet
            $manager->persist($statut);
        }

        $manager->flush();

        $allTags = $manager->getRepository(Tag::class)->findAll();
        $status = $manager->getRepository(Status::class)->findAll();
        $listUsers = $manager->getRepository(User::class)->findAll();


        for ($t = 0; $t < mt_rand(15, 30); $t++) {

            $task = new Task();
            $task->setName($faker->sentence(6));
            $task->setDescription($faker->paragraph(3));
            $task->setCreatedAt(new \DateTime());
            $task->setDueAt($faker->dateTimeBetween('now', '15 days'));
            $task->setTag($faker->randomElement($allTags));
            $task->setUser($faker->randomElement($listUsers));
            $task->setStatus($faker->randomElement($status));
            $task->setIsArchived(0);

            $manager->persist($task);
        }

        $manager->flush();
    }
}
