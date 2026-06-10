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

class InscriptionEtatTest extends WebTestCase
{
    private const URL_FORMATION = '/formations/formation/';
    private const SELECTEUR_TOKEN_ETAT = 'form[action$="/etat"] input[name="_token"]';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private ?Playlist $playlist = null;
    private ?Formation $formation = null;
    private ?User $user = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->playlist = (new Playlist())->setName('P-suivi-' . uniqid())->setDescription('Description test');

        $this->formation = (new Formation())
            ->setTitle('Formation suivi test')
            ->setVideoId('video-suivi-' . uniqid())
            ->setPublishedAt(new \DateTime('2024-01-01'))
            ->setPlaylist($this->playlist)
            ->setDescription('Description test');

        $this->user = (new User())
            ->setUsername('user_suivi_test_' . uniqid())
            ->setRoles(['ROLE_USER'])
            ->setPassword('test');

        $this->entityManager->persist($this->playlist);
        $this->entityManager->persist($this->formation);
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $em = $this->entityManager;

        $inscription = $em->getRepository(Inscription::class)
            ->findOneByUserAndFormation($this->user, $this->formation);
        if ($inscription !== null) {
            $em->remove($inscription);
            $em->flush();
        }

        $em->remove($em->getReference(Formation::class, $this->formation->getId()));
        $em->remove($em->getReference(Playlist::class, $this->playlist->getId()));
        $em->remove($em->getReference(User::class, $this->user->getId()));
        $em->flush();

        parent::tearDown();
    }

    // inscription

    public function testInscriptionAnonymeRedirigeVersLogin(): void
    {
        $this->client->request('POST', $this->formationUrl() . '/inscription', [
            '_token' => 'peu-importe',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testInscriptionUtilisateurConnecte(): void
    {
        $this->client->loginUser($this->user, 'main');

        $crawler = $this->client->request('GET', $this->formationUrl());
        $token = $crawler->filter('form[action$="/inscription"] input[name="_token"]')->attr('value');

        $this->client->request('POST', $this->formationUrl() . '/inscription', [
            '_token' => $token,
        ]);

        self::assertResponseRedirects($this->formationUrl());

        $inscription = $this->entityManager->getRepository(Inscription::class)
            ->findOneByUserAndFormation($this->user, $this->formation);

        self::assertNotNull($inscription);
        self::assertSame(EtatAvancement::NON_COMMENCEE, $inscription->getEtat());
    }

    // transitions d'état

    public function testTransitionVersEtatSuivantAutorisee(): void
    {
        $this->client->loginUser($this->user, 'main');
        $this->enroll();

        $crawler = $this->client->request('GET', $this->formationUrl());
        $token = $crawler->filter(self::SELECTEUR_TOKEN_ETAT)->attr('value');

        $this->client->request('POST', $this->etatUrl(), [
            '_token' => $token,
            'etat' => 'en_cours',
        ]);

        self::assertResponseRedirects($this->formationUrl());

        self::assertSame(EtatAvancement::EN_COURS, $this->reloadInscription()->getEtat());
    }

    public function testTransitionEnSautantUneEtapeRefusee(): void
    {
        $this->client->loginUser($this->user, 'main');
        $this->enroll();

        $crawler = $this->client->request('GET', $this->formationUrl());
        $token = $crawler->filter(self::SELECTEUR_TOKEN_ETAT)->attr('value');

        $this->client->request('POST', $this->etatUrl(), [
            '_token' => $token,
            'etat' => 'terminee',
        ]);

        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', "Transition d'état invalide");
        self::assertSame(EtatAvancement::NON_COMMENCEE, $this->reloadInscription()->getEtat());
    }

    public function testTransitionEnArriereRefusee(): void
    {
        $this->client->loginUser($this->user, 'main');
        $inscription = $this->enroll();

        $crawler = $this->client->request('GET', $this->formationUrl());
        $token = $crawler->filter(self::SELECTEUR_TOKEN_ETAT)->attr('value');

        $inscription->setEtat(EtatAvancement::TERMINEE);
        $this->entityManager->flush();

        $this->client->request('POST', $this->etatUrl(), [
            '_token' => $token,
            'etat' => 'en_cours',
        ]);

        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', "Transition d'état invalide");
        self::assertSame(EtatAvancement::TERMINEE, $this->reloadInscription()->getEtat());
    }

    private function formationUrl(): string
    {
        return self::URL_FORMATION . $this->formation->getId();
    }

    private function etatUrl(): string
    {
        return $this->formationUrl() . '/etat';
    }

    private function enroll(): Inscription
    {
        $inscription = (new Inscription())->setUser($this->user)->setFormation($this->formation);
        $this->entityManager->persist($inscription);
        $this->entityManager->flush();

        return $inscription;
    }

    private function reloadInscription(): Inscription
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(Inscription::class)
            ->findOneByUserAndFormation($this->user, $this->formation);
    }
}
