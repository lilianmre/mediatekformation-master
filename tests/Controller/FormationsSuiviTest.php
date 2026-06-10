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

class FormationsSuiviTest extends WebTestCase
{
    private const URL_FORMATIONS = '/formations';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private ?Playlist $playlist = null;
    private ?Formation $formationSuivie = null;
    private ?Formation $formationNonSuivie = null;
    private ?User $user = null;
    private ?Inscription $inscription = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->playlist = (new Playlist())->setName('P-suivi-list-' . uniqid())->setDescription('Description test');

        $this->formationSuivie = (new Formation())
            ->setTitle('Formation suivie test')
            ->setVideoId('video-suivie-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->formationNonSuivie = (new Formation())
            ->setTitle('Formation non suivie test')
            ->setVideoId('video-non-suivie-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-02'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->user = (new User())
            ->setUsername('user_suivi_list_test_' . uniqid())
            ->setRoles(['ROLE_USER'])
            ->setPassword('test');

        $this->entityManager->persist($this->playlist);
        $this->entityManager->persist($this->formationSuivie);
        $this->entityManager->persist($this->formationNonSuivie);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        $this->inscription = (new Inscription())->setUser($this->user)->setFormation($this->formationSuivie);
        $this->inscription->setEtat(EtatAvancement::EN_COURS);
        $this->entityManager->persist($this->inscription);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $em = $this->entityManager;

        $em->remove($em->getReference(Inscription::class, $this->inscription->getId()));
        $em->flush();

        $em->remove($em->getReference(Formation::class, $this->formationSuivie->getId()));
        $em->remove($em->getReference(Formation::class, $this->formationNonSuivie->getId()));
        $em->remove($em->getReference(Playlist::class, $this->playlist->getId()));
        $em->remove($em->getReference(User::class, $this->user->getId()));
        $em->flush();

        parent::tearDown();
    }

    // colonne "mon suivi"

    public function testColonneSuiviAbsentePourAnonyme(): void
    {
        $crawler = $this->client->request('GET', self::URL_FORMATIONS);

        self::assertResponseStatusCodeSame(200);
        self::assertCount(0, $crawler->filterXPath("//th[contains(., 'mon suivi')]"));
        self::assertCount(0, $crawler->filterXPath("//td[contains(., 'Non inscrit')]"));
    }

    public function testColonneSuiviPresentePourUtilisateurConnecte(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('GET', self::URL_FORMATIONS);

        self::assertResponseStatusCodeSame(200);
        self::assertCount(1, $crawler->filterXPath("//th[contains(., 'mon suivi')]"));

        $ligneSuivie = $crawler->filterXPath("//tr[.//h5[contains(text(), 'Formation suivie test')]]");
        self::assertSame(1, $ligneSuivie->filter('span.badge.bg-warning')->count());

        $ligneNonSuivie = $crawler->filterXPath("//tr[.//h5[contains(text(), 'Formation non suivie test')]]");
        self::assertStringContainsString('Non inscrit', $ligneNonSuivie->text());
    }

    // filtre par état

    public function testFiltreSuiviParEtat(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('POST', '/formations/recherche-etat', [
            'recherche' => 'en_cours',
            '_token' => $this->csrfTokenFiltreEtat(),
        ]);

        self::assertResponseStatusCodeSame(200);

        $titres = $crawler->filter('tbody tr h5.text-info')->each(fn($node) => trim($node->text()));
        self::assertSame(['Formation suivie test'], $titres);
    }

    public function testFiltreSuiviNonInscrit(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('POST', '/formations/recherche-etat', [
            'recherche' => 'non_inscrit',
            '_token' => $this->csrfTokenFiltreEtat(),
        ]);

        self::assertResponseStatusCodeSame(200);

        $titres = $crawler->filter('tbody tr h5.text-info')->each(fn($node) => trim($node->text()));
        self::assertContains('Formation non suivie test', $titres);
        self::assertNotContains('Formation suivie test', $titres);
    }

    private function csrfTokenFiltreEtat(): string
    {
        $crawler = $this->client->request('GET', self::URL_FORMATIONS);

        return $crawler->filterXPath("//th[contains(., 'mon suivi')]//input[@name='_token']")->attr('value');
    }
}
