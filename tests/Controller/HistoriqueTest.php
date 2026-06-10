<?php

namespace App\Tests\Controller;

use App\Entity\EtatAvancement;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HistoriqueTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private ?Playlist $playlist = null;
    private ?Formation $formationTermineeRecente = null;
    private ?Formation $formationTermineeAncienne = null;
    private ?Formation $formationEnCours = null;
    private ?User $user = null;

    /** @var int[] */
    private array $inscriptionIds = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->playlist = (new Playlist())->setName('P-historique-' . uniqid())->setDescription('Description test');

        $this->formationTermineeRecente = (new Formation())
            ->setTitle('Formation terminee recente')
            ->setVideoId('video-hist-1-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->formationTermineeAncienne = (new Formation())
            ->setTitle('Formation terminee ancienne')
            ->setVideoId('video-hist-2-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-02'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->formationEnCours = (new Formation())
            ->setTitle('Formation en cours')
            ->setVideoId('video-hist-3-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-03'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->user = (new User())
            ->setUsername('user_historique_test_' . uniqid())
            ->setRoles(['ROLE_USER'])
            ->setPassword('test');

        $this->entityManager->persist($this->playlist);
        $this->entityManager->persist($this->formationTermineeRecente);
        $this->entityManager->persist($this->formationTermineeAncienne);
        $this->entityManager->persist($this->formationEnCours);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        $termineeRecente = (new Inscription())->setUser($this->user)->setFormation($this->formationTermineeRecente);
        $termineeRecente->setEtat(EtatAvancement::TERMINEE);
        $termineeRecente->setDateValidation(new \DateTime('-1 day'));

        $termineeAncienne = (new Inscription())->setUser($this->user)->setFormation($this->formationTermineeAncienne);
        $termineeAncienne->setEtat(EtatAvancement::TERMINEE);
        $termineeAncienne->setDateValidation(new \DateTime('-10 days'));

        $enCours = (new Inscription())->setUser($this->user)->setFormation($this->formationEnCours);
        $enCours->setEtat(EtatAvancement::EN_COURS);

        foreach ([$termineeRecente, $termineeAncienne, $enCours] as $inscription) {
            $this->entityManager->persist($inscription);
        }
        $this->entityManager->flush();

        $this->inscriptionIds = [$termineeRecente->getId(), $termineeAncienne->getId(), $enCours->getId()];
    }

    protected function tearDown(): void
    {
        $em = $this->entityManager;

        foreach ($this->inscriptionIds as $id) {
            $entity = $em->find(Inscription::class, $id);
            if ($entity !== null) {
                $em->remove($entity);
            }
        }
        $em->flush();

        $em->remove($em->getReference(Formation::class, $this->formationTermineeRecente->getId()));
        $em->remove($em->getReference(Formation::class, $this->formationTermineeAncienne->getId()));
        $em->remove($em->getReference(Formation::class, $this->formationEnCours->getId()));
        $em->remove($em->getReference(Playlist::class, $this->playlist->getId()));
        $em->remove($em->getReference(User::class, $this->user->getId()));
        $em->flush();

        parent::tearDown();
    }

    // accès

    public function testAccesAnonymeRedirigeVersConnexion(): void
    {
        $this->client->request('GET', '/historique');

        self::assertResponseRedirects('/connexion');
    }

    public function testAccesTermineesAnonymeRedirigeVersConnexion(): void
    {
        $this->client->request('GET', '/historique/terminees');

        self::assertResponseRedirects('/connexion');
    }

    // historique complet

    public function testHistoriqueListeToutesLesInscriptions(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('GET', '/historique');

        self::assertResponseStatusCodeSame(200);

        $titres = $crawler->filter('tbody tr td a')->each(fn($node) => trim($node->text()));
        self::assertContains('Formation terminee recente', $titres);
        self::assertContains('Formation terminee ancienne', $titres);
        self::assertContains('Formation en cours', $titres);
    }

    // historique des formations terminées, triées par date de validation décroissante

    public function testHistoriqueTermineesOrdonneesParDateValidationDesc(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('GET', '/historique/terminees');

        self::assertResponseStatusCodeSame(200);

        $titres = $crawler->filter('tbody tr td a')->each(fn($node) => trim($node->text()));
        self::assertSame(['Formation terminee recente', 'Formation terminee ancienne'], $titres);
    }
}
