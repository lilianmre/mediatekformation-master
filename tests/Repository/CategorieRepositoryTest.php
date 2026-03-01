<?php

namespace App\Tests\Repository;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategorieRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CategorieRepository $categorieRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->categorieRepository = $this->entityManager->getRepository(Categorie::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    // add()

    public function testAddCategorie(): void
    {
        $countBefore = $this->categorieRepository->count([]);

        $categorie = (new Categorie())->setName('Categorie Add Test');
        $this->categorieRepository->add($categorie);

        $countAfter = $this->categorieRepository->count([]);

        self::assertSame($countBefore + 1, $countAfter);
        self::assertNotNull($categorie->getId());

        // cleanup
        $this->entityManager->remove($categorie);
        $this->entityManager->flush();
    }

    // remove()

    public function testRemoveCategorie(): void
    {
        $categorie = (new Categorie())->setName('Categorie Remove Test');
        $this->entityManager->persist($categorie);
        $this->entityManager->flush();

        $countBefore = $this->categorieRepository->count([]);
        $this->categorieRepository->remove($categorie);
        $countAfter = $this->categorieRepository->count([]);

        self::assertSame($countBefore - 1, $countAfter);
    }

    // findAllForOnePlaylist()

    public function testFindAllForOnePlaylist(): void
    {
        $playlist = (new Playlist())
            ->setName('Playlist test catégories')
            ->setDescription('Description test');
        $this->entityManager->persist($playlist);

        $categorieA = (new Categorie())->setName('Categorie FindAll A');
        $categorieB = (new Categorie())->setName('Categorie FindAll B');
        $this->entityManager->persist($categorieA);
        $this->entityManager->persist($categorieB);

        $formation = (new Formation())
            ->setTitle('Formation test catégories')
            ->setVideoId('video-cat-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($playlist)
            ->setDescription('Description test');
    
        $formation->addCategory($categorieA);
        $formation->addCategory($categorieB);
        $this->entityManager->persist($formation);
        $this->entityManager->flush();

        $results = $this->categorieRepository->findAllForOnePlaylist($playlist->getId());

        self::assertCount(2, $results);
        $names = array_map(fn(Categorie $c) => $c->getName(), $results);
        self::assertContains('Categorie FindAll A', $names);
        self::assertContains('Categorie FindAll B', $names);

        // cleanup
        $formation->removeCategory($categorieA);
        $formation->removeCategory($categorieB);
        $this->entityManager->flush();
        $this->entityManager->remove($formation);
        $this->entityManager->remove($categorieA);
        $this->entityManager->remove($categorieB);
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }


}
