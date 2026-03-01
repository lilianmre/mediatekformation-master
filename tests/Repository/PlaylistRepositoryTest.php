<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Playlist;
use App\Repository\PlaylistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PlaylistRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PlaylistRepository $playlistRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->playlistRepository = $this->entityManager->getRepository(Playlist::class);
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

    private function createFormation(Playlist $playlist, string $title): Formation
    {
        $formation = (new Formation())
            ->setTitle($title)
            ->setVideoId('video-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($playlist)
            ->setDescription('Description test');
        $this->entityManager->persist($formation);
        $this->entityManager->flush();
        return $formation;
    }

    // add()

    public function testAddPlaylist(): void
    {
        $countBefore = $this->playlistRepository->count([]);

        $playlist = (new Playlist())
            ->setName('AAAAAA')
            ->setDescription('Description add test');

        $this->playlistRepository->add($playlist);
        $countAfter = $this->playlistRepository->count([]);

        self::assertSame($countBefore + 1, $countAfter);
        self::assertNotNull($playlist->getId());

        // cleanup
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }

    // remove()

    public function testRemovePlaylist(): void
    {
        $playlist = $this->createPlaylist('AAAAAA');

        $countBefore = $this->playlistRepository->count([]);
        $this->playlistRepository->remove($playlist);
        $countAfter = $this->playlistRepository->count([]);

        self::assertSame($countBefore - 1, $countAfter);
    }

    // findAllOrderByName()

    public function testFindAllOrderByNameAsc(): void
    {
        $playlistA = $this->createPlaylist('AAAAAA');
        $playlistZ = $this->createPlaylist('ZZZZZZ');

        $results = $this->playlistRepository->findAllOrderByName('ASC');

        self::assertNotEmpty($results);
        $names = array_map(fn(Playlist $p) => $p->getName(), $results);
        $sorted = $names;
        sort($sorted);
        self::assertSame($sorted, $names);

        // cleanup
        $this->entityManager->remove($playlistA);
        $this->entityManager->remove($playlistZ);
        $this->entityManager->flush();
    }

    // findAllOrderByFormationCount()

    public function testFindAllOrderByFormationCountDesc(): void
    {
        $playlistFew  = $this->createPlaylist('AAAAAA');
        $playlistMany = $this->createPlaylist('ZZZZZZ');

        $f1 = $this->createFormation($playlistMany, 'A');
        $f2 = $this->createFormation($playlistMany, 'B');
        $f3 = $this->createFormation($playlistMany, 'C');
        $f4 = $this->createFormation($playlistFew,  'D');

        $results = $this->playlistRepository->findAllOrderByFormationCount('DESC');

        self::assertNotEmpty($results);
        $resultIds = array_map(fn(Playlist $p) => $p->getId(), $results);
        $indexFew  = array_search($playlistFew->getId(),  $resultIds);
        $indexMany = array_search($playlistMany->getId(), $resultIds);
        // La playlist la plus grande doit apparaître avant la plus petite
        self::assertLessThan($indexFew, $indexMany);

        // cleanup
        $this->entityManager->remove($f1);
        $this->entityManager->remove($f2);
        $this->entityManager->remove($f3);
        $this->entityManager->remove($f4);
        $this->entityManager->remove($playlistFew);
        $this->entityManager->remove($playlistMany);
        $this->entityManager->flush();
    }

    // findByContainValue()

    public function testFindByContainValuePlaylistByName(): void
    {
        $playlist = $this->createPlaylist('AAAAAA');

        $results = $this->playlistRepository->findByContainValue('name', 'AAAAAA');

        self::assertNotEmpty($results);
        $ids = array_map(fn(Playlist $p) => $p->getId(), $results);
        self::assertContains($playlist->getId(), $ids);

        // cleanup
        $this->entityManager->remove($playlist);
        $this->entityManager->flush();
    }
}
