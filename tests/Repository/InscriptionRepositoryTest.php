<?php

namespace App\Tests\Repository;

use App\Entity\EtatAvancement;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\Playlist;
use App\Entity\User;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InscriptionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private InscriptionRepository $inscriptionRepository;

    private ?Playlist $playlist = null;
    private ?Formation $formation1 = null;
    private ?Formation $formation2 = null;
    private ?User $user = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->inscriptionRepository = $this->entityManager->getRepository(Inscription::class);

        $this->playlist = (new Playlist())->setName('P-insc-' . uniqid())->setDescription('Description test');

        $this->formation1 = (new Formation())
            ->setTitle('Formation insc 1')
            ->setVideoId('video-insc-1-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->formation2 = (new Formation())
            ->setTitle('Formation insc 2')
            ->setVideoId('video-insc-2-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-02'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->user = (new User())
            ->setUsername('user_insc_test_' . uniqid())
            ->setRoles(['ROLE_USER'])
            ->setPassword('test');

        $this->entityManager->persist($this->playlist);
        $this->entityManager->persist($this->formation1);
        $this->entityManager->persist($this->formation2);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        foreach ($this->inscriptionRepository->findAllByUser($this->user) as $inscription) {
            $this->entityManager->remove($inscription);
        }
        $this->entityManager->flush();

        $this->entityManager->remove($this->formation1);
        $this->entityManager->remove($this->formation2);
        $this->entityManager->remove($this->playlist);
        $this->entityManager->remove($this->user);
        $this->entityManager->flush();

        parent::tearDown();
        $this->entityManager->close();
    }

    // add() / findOneByUserAndFormation()

    public function testAddAndFindOneByUserAndFormation(): void
    {
        $inscription = (new Inscription())->setUser($this->user)->setFormation($this->formation1);

        $this->inscriptionRepository->add($inscription);

        self::assertNotNull($inscription->getId());

        $found = $this->inscriptionRepository->findOneByUserAndFormation($this->user, $this->formation1);

        self::assertNotNull($found);
        self::assertSame($inscription->getId(), $found->getId());
    }

    public function testFindOneByUserAndFormationReturnsNullWhenNotEnrolled(): void
    {
        $found = $this->inscriptionRepository->findOneByUserAndFormation($this->user, $this->formation2);

        self::assertNull($found);
    }

    // findAllByUser()

    public function testFindAllByUser(): void
    {
        $this->inscriptionRepository->add((new Inscription())->setUser($this->user)->setFormation($this->formation1));
        $this->inscriptionRepository->add((new Inscription())->setUser($this->user)->setFormation($this->formation2));

        $results = $this->inscriptionRepository->findAllByUser($this->user);

        self::assertCount(2, $results);
    }

    // findAllByUserOrderByDateInscription()

    public function testFindAllByUserOrderByDateInscription(): void
    {
        $ancienne = (new Inscription())->setUser($this->user)->setFormation($this->formation1);
        $ancienne->setDateInscription(new \DateTime('-2 days'));

        $recente = (new Inscription())->setUser($this->user)->setFormation($this->formation2);
        $recente->setDateInscription(new \DateTime('-1 hour'));

        $this->inscriptionRepository->add($ancienne);
        $this->inscriptionRepository->add($recente);

        $results = $this->inscriptionRepository->findAllByUserOrderByDateInscription($this->user);

        self::assertCount(2, $results);
        self::assertSame($recente->getId(), $results[0]->getId());
        self::assertSame($ancienne->getId(), $results[1]->getId());
    }

    // findTermineesByUserOrderByDateValidation()

    public function testFindTermineesByUserOrderByDateValidation(): void
    {
        $termineeAncienne = (new Inscription())->setUser($this->user)->setFormation($this->formation1);
        $termineeAncienne->setEtat(EtatAvancement::TERMINEE);
        $termineeAncienne->setDateValidation(new \DateTime('-10 days'));

        $termineeRecente = (new Inscription())->setUser($this->user)->setFormation($this->formation2);
        $termineeRecente->setEtat(EtatAvancement::TERMINEE);
        $termineeRecente->setDateValidation(new \DateTime('-1 day'));

        $this->inscriptionRepository->add($termineeAncienne);
        $this->inscriptionRepository->add($termineeRecente);

        $results = $this->inscriptionRepository->findTermineesByUserOrderByDateValidation($this->user);

        self::assertCount(2, $results);
        self::assertSame($termineeRecente->getId(), $results[0]->getId());
        self::assertSame($termineeAncienne->getId(), $results[1]->getId());
    }

    public function testFindTermineesExcludesNonTerminees(): void
    {
        $enCours = (new Inscription())->setUser($this->user)->setFormation($this->formation1);
        $enCours->setEtat(EtatAvancement::EN_COURS);

        $this->inscriptionRepository->add($enCours);

        $results = $this->inscriptionRepository->findTermineesByUserOrderByDateValidation($this->user);

        self::assertCount(0, $results);
    }
}
