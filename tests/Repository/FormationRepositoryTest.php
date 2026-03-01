<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormationRepositoryTest extends KernelTestCase
{
    private const DEFAULT_DATE = '2024-01-01';

    private EntityManagerInterface $entityManager;
    private FormationRepository $formationRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->formationRepository = $this->entityManager->getRepository(Formation::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createPlaylist(string $name): Playlist
    {
        $playlist = (new Playlist())
            ->setName($name)
            ->setDescription('Description test');
        $this->entityManager->persist($playlist);
        $this->entityManager->flush();
        return $playlist;
    }

    private function createFormation(Playlist $playlist, string $title, string $date = self::DEFAULT_DATE): Formation
    {
        $formation = (new Formation())
            ->setTitle($title)
            ->setVideoId('video' . uniqid())
            ->setPublishedAt(new \DateTime($date))
            ->setPlaylist($playlist)
            ->setDescription('Description test');
        $this->entityManager->persist($formation);
        $this->entityManager->flush();
        return $formation;
    }

    // add()

    public function testAddFormation(): void
    {
        $playlist = $this->createPlaylist('P');

        $formation = (new Formation())
            ->setTitle('AAAAAA')
            ->setVideoId('video-add' . uniqid())
            ->setPublishedAt(new \DateTime(self::DEFAULT_DATE))
            ->setPlaylist($playlist)
            ->setDescription('Description add test');

        $countBefore = $this->formationRepository->count([]);
        $this->formationRepository->add($formation);
        $countAfter = $this->formationRepository->count([]);

        self::assertSame($countBefore + 1, $countAfter);
        self::assertNotNull($formation->getId());

        // cleanup
        $this->entityManager->remove($formation);
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // remove()

    public function testRemoveFormation(): void
    {
        $playlist = $this->createPlaylist('P');
        $formation = $this->createFormation($playlist, 'AAAAAA');

        $countBefore = $this->formationRepository->count([]);
        $this->formationRepository->remove($formation);
        $countAfter = $this->formationRepository->count([]);

        self::assertSame($countBefore - 1, $countAfter);

        // cleanup
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // findAllOrderBy()

    public function testFindAllOrderByTitleAsc(): void
    {
        $playlist = $this->createPlaylist('P');
        $formationA = $this->createFormation($playlist, 'AAAAAA');
        $formationZ = $this->createFormation($playlist, 'ZZZZZZ');

        $results = $this->formationRepository->findAllOrderBy('title', 'ASC');

        self::assertNotEmpty($results);
        $titles = array_map(fn(Formation $f) => $f->getTitle(), $results);
        $sorted = $titles;
        sort($sorted);
        self::assertSame($sorted, $titles);

        // cleanup
        $this->entityManager->remove($formationA);
        $this->entityManager->remove($formationZ);
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // findByContainValue()

    public function testFindByContainValueFormationByTitle(): void
    {
        $playlist = $this->createPlaylist('P');
        $formation = $this->createFormation($playlist, 'AAAAAA');

        $results = $this->formationRepository->findByContainValue('title', 'AAAAAA');

        self::assertNotEmpty($results);
        $ids = array_map(fn(Formation $f) => $f->getId(), $results);
        self::assertContains($formation->getId(), $ids);

        // cleanup
        $this->entityManager->remove($formation);
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // findAllLasted()

    public function testFindAllLasted(): void
    {
        $playlist = $this->createPlaylist('P');
        $formationOld  = $this->createFormation($playlist, 'AAAAAA', '2023-01-01');
        $formationMid  = $this->createFormation($playlist, 'MMMMMM', self::DEFAULT_DATE);
        $formationNew  = $this->createFormation($playlist, 'ZZZZZZ', '2025-01-01');

        $results = $this->formationRepository->findAllLasted(2);

        self::assertCount(2, $results);
        // Les plus récentes doivent apparaître en premier
        self::assertSame($formationNew->getId(), $results[0]->getId());
        self::assertSame($formationMid->getId(), $results[1]->getId());

        // cleanup
        $this->entityManager->remove($formationOld);
        $this->entityManager->remove($formationMid);
        $this->entityManager->remove($formationNew);
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // findAllForOnePlaylist()

    public function testFindAllForOnePlaylist(): void
    {
        $playlist1 = $this->createPlaylist('P1');
        $playlist2 = $this->createPlaylist('P2');
        $formation1 = $this->createFormation($playlist1, 'AAAAAA', self::DEFAULT_DATE);
        $formation2 = $this->createFormation($playlist1, 'MMMMMM', '2024-03-01');
        $formation3 = $this->createFormation($playlist2, 'ZZZZZZ', '2024-02-01');

        $results = $this->formationRepository->findAllForOnePlaylist($playlist1->getId());

        self::assertCount(2, $results);
        foreach ($results as $formation) {
            self::assertSame($playlist1->getId(), $formation->getPlaylist()->getId());
        }

        // cleanup
        $this->entityManager->remove($formation1);
        $this->entityManager->remove($formation2);
        $this->entityManager->remove($formation3);
        $this->entityManager->remove($playlist1);
        $this->entityManager->remove($playlist2);
        $this->entityManager->flush();
    }


}