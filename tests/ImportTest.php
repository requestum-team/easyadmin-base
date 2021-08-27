<?php


namespace App\Tests;

use App\Entity\User;
use App\Service\UserImport\ImporterInterface;
use App\Service\UserImport\ImportException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;

class ImportTest extends KernelTestCase
{
    public function importDataProvider() {
        return [
            [
                $file = new File(__DIR__ . '/data/valid.csv'),
                2
            ],
            [
                $file = new File(__DIR__ . '/data/invalid_data.csv'),
                False,
            ],
            [
                $file = new File(__DIR__ . '/data/invalid_structure.csv'),
                False,
            ],
        ];
    }

    /** @dataProvider importDataProvider */
    public function testImport($file, $expected)
    {
        self::bootKernel();

        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery(sprintf('DELETE FROM %s', User::class))->execute();
        $importer = $container->get(ImporterInterface::class);

        if ($expected === False) {
            $this->expectException(ImportException::class);
            $importer->import($file);
        } else {
            $created = $importer->import($file);
            $this->assertEquals($expected, $created);
            $this->assertEmailCount($expected);
        }
    }
}
