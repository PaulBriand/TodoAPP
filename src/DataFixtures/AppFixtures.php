<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($t = 0; $t < mt_rand(15, 30); $t++) {
            $task = new Task();

            $task->setName($faker->sentence(6));
            $task->setDescription($faker->paragraph(3));
            $task->setCreatedAt(new \DateTime());
            $task->setDueAt($faker->dateTimeBetween('now', '15 days'));

            $manager->persist($task);
        }

        $manager->flush();
    }
}
