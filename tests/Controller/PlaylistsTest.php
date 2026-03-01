<?php

namespace App\Tests\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlaylistsTest extends WebTestCase
{
    private const URL_PLAYLISTS  = '/playlists';
    private const NOM_PLAYLIST = 'AAAAAA';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private ?int $playlist1Id = null;  // "AAAAAA"
    private ?int $playlist2Id = null;  // "ZZZZZZ"

    protected function setUp(): void
    {
        $this->client        = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $playlist1 = (new Playlist())->setName(self::NOM_PLAYLIST)->setDescription('Test');
        $playlist2 = (new Playlist())->setName('ZZZZZZ')->setDescription('Test');
        $this->entityManager->persist($playlist1);
        $this->entityManager->persist($playlist2);
        $this->entityManager->flush();

        $this->playlist1Id = $playlist1->getId();
        $this->playlist2Id = $playlist2->getId();
    }

    protected function tearDown(): void
    {
        $em = $this->entityManager;

        foreach ([$this->playlist1Id, $this->playlist2Id] as $id) {
            $entity = $em->find(Playlist::class, $id);
            if ($entity !== null) {
                $em->remove($entity);
            }
        }
        $em->flush();

        parent::tearDown();
    }

    // page accessible

    public function testPagePlaylists(): void
    {
        $this->client->request('GET', self::URL_PLAYLISTS);

        self::assertResponseStatusCodeSame(200);
    }

    // tri

    public function testTriParNomAsc(): void
    {
        $crawler = $this->client->request('GET', self::URL_PLAYLISTS . '/tri/name/ASC');

        self::assertResponseStatusCodeSame(200);

        $premierNom = $crawler->filter('tbody tr')->first()->filter('td h5.text-info')->text();
        self::assertSame(self::NOM_PLAYLIST, $premierNom);
    }

    // filtre

    public function testFiltreParNomNombreDeLignes(): void
    {
        $crawler = $this->client->request('GET', self::URL_PLAYLISTS);

        $form = $crawler->filter('form[action$="' . self::URL_PLAYLISTS . '/recherche/name"]')->form([
            'recherche' => self::NOM_PLAYLIST,
        ]);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);

        $lignes = $crawler->filter('tbody tr');
        self::assertSame(1, $lignes->count());

        $premierNom = $lignes->first()->filter('td h5.text-info')->text();
        self::assertSame(self::NOM_PLAYLIST, $premierNom);
    }

    // clic sur un bouton de la liste

    public function testClicSurPlaylist(): void
    {
        $crawler = $this->client->request('GET', self::URL_PLAYLISTS);

        $lien = $crawler->filter(
            'tbody tr a.btn-secondary[href$="' . self::URL_PLAYLISTS . '/playlist/' . $this->playlist1Id . '"]'
        )->first()->link();

        $this->client->click($lien);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('h4.text-info', self::NOM_PLAYLIST);
    }
}
