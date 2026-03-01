<?php

namespace App\Tests\Controller;

use App\Entity\Formation;
use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormationsTest extends WebTestCase
{
    private const URL_FORMATIONS = '/formations';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    private ?int $playlist1Id  = null;
    private ?int $playlist2Id  = null;
    private ?int $formation1Id = null;  // titre "AAAAAA"
    private ?int $formation2Id = null;  // titre "ZZZZZZ"
    private ?int $formation3Id = null;  // titre "MMMMMM"

    protected function setUp(): void
    {
        $this->client        = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $playlist1 = (new Playlist())->setName('A')->setDescription('Test');
        $playlist2 = (new Playlist())->setName('Z')->setDescription('Test');
        $this->entityManager->persist($playlist1);
        $this->entityManager->persist($playlist2);

        $formation1 = (new Formation())
            ->setTitle('AAAAAA')
            ->setVideoId('functest-1')
            ->setPublishedAt(new \DateTime('2020-01-01'))
            ->setPlaylist($playlist1)
            ->setDescription('Test');

        $formation2 = (new Formation())
            ->setTitle('ZZZZZZ')
            ->setVideoId('functest-2')
            ->setPublishedAt(new \DateTime('2022-01-01'))
            ->setPlaylist($playlist2)
            ->setDescription('Test');

        $formation3 = (new Formation())
            ->setTitle('MMMMMM')
            ->setVideoId('functest-3')
            ->setPublishedAt(new \DateTime('2025-01-01'))
            ->setPlaylist($playlist1)
            ->setDescription('Test');

        $this->entityManager->persist($formation1);
        $this->entityManager->persist($formation2);
        $this->entityManager->persist($formation3);
        $this->entityManager->flush();

        $this->playlist1Id  = $playlist1->getId();
        $this->playlist2Id  = $playlist2->getId();
        $this->formation1Id = $formation1->getId();
        $this->formation2Id = $formation2->getId();
        $this->formation3Id = $formation3->getId();
    }

    protected function tearDown(): void
    {
        $em = $this->entityManager;

        foreach ([$this->formation1Id, $this->formation2Id, $this->formation3Id] as $id) {
            $entity = $em->find(Formation::class, $id);
            if ($entity !== null) {
                $em->remove($entity);
            }
        }
        $em->flush();

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

    public function testPageFormations(): void
    {
        $this->client->request('GET', self::URL_FORMATIONS);

        self::assertResponseStatusCodeSame(200);
    }

    // tri

    public function testTriParTitreAsc(): void
    {
        $crawler = $this->client->request('GET', self::URL_FORMATIONS . '/tri/title/ASC');

        self::assertResponseStatusCodeSame(200);

        $premierTitre = $crawler->filter('tbody tr')->first()->filter('td h5.text-info')->text();
        self::assertSame('AAAAAA', $premierTitre);
    }

    // filtre

    public function testFiltreParTitreNombreDeLignes(): void
    {
        $crawler = $this->client->request('GET', self::URL_FORMATIONS);

        $form = $crawler->filter('form[action$="' . self::URL_FORMATIONS . '/recherche/title"]')->form([
            'recherche' => 'AAAAAA',
        ]);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);

        $lignes = $crawler->filter('tbody tr');
        self::assertSame(1, $lignes->count());

        $premierTitre = $lignes->first()->filter('td h5.text-info')->text();
        self::assertSame('AAAAAA', $premierTitre);
    }

    // clic sur un lien de la liste

    public function testClicSurFormation(): void
    {
        $crawler = $this->client->request('GET', self::URL_FORMATIONS);

        $lien = $crawler->filter(
            'tbody tr a[href$="' . self::URL_FORMATIONS . '/formation/' . $this->formation1Id . '"]'
        )->first()->link();

        $this->client->click($lien);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('h4.text-info', 'AAAAAA');
    }
}
