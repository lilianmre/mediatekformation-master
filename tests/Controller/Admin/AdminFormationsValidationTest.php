<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Formation;
use App\Entity\Playlist;
use App\Entity\User;
use App\Kernel;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminFormationsValidationTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAddRejectsFuturePublishedAtDate(): void
    {
        $client = static::createClient();

        $entityManager = $this->getEntityManager();
        $adminUser = $this->createAndLoginAdmin($client, $entityManager);
        $formationRepository = static::getContainer()->get(FormationRepository::class);

        $playlist = (new Playlist())
            ->setName('Playlist test ajout date future')
            ->setDescription('Playlist de test');

        $entityManager->persist($playlist);
        $entityManager->flush();

        $countBefore = $formationRepository->count([]);

        $crawler = $client->request('GET', '/admin/formations/add');
        $form = $crawler->selectButton('Enregistrer')->form([
            'formation[title]' => 'Formation test date future ADD',
            'formation[videoId]' => 'video-test-1',
            'formation[publishedAt]' => (new \DateTime('+1 day'))->format('Y-m-d'),
            'formation[playlist]' => (string) $playlist->getId(),
            'formation[description]' => 'Description de test',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains(
            'body',
            'La date de publication ne peut pas être postérieure à aujourd\'hui.'
        );
        self::assertSame($countBefore, $formationRepository->count([]));

        // cleanup (use references to avoid "detached entity" errors)
        $entityManager->remove($entityManager->getReference(Playlist::class, $playlist->getId()));
        $entityManager->remove($entityManager->getReference(User::class, $adminUser->getId()));
        $entityManager->flush();
    }

    public function testEditRejectsFuturePublishedAtDate(): void
    {
        $client = static::createClient();

        $entityManager = $this->getEntityManager();
        $adminUser = $this->createAndLoginAdmin($client, $entityManager);

        $playlist = (new Playlist())
            ->setName('Playlist test date future EDIT')
            ->setDescription('Playlist de test');

        $formation = (new Formation())
            ->setTitle('Formation à modifier')
            ->setVideoId('video-test-2')
            ->setPublishedAt(new \DateTime('2024-05-13'))
            ->setPlaylist($playlist)
            ->setDescription('Description initiale');

        $entityManager->persist($playlist);
        $entityManager->persist($formation);
        $entityManager->flush();

        $formationId = $formation->getId();
        self::assertNotNull($formationId);

        $dateBefore = $formation->getPublishedAt()?->format('Y-m-d');

        $crawler = $client->request('GET', '/admin/formations/' . $formationId . '/edit');
        $form = $crawler->selectButton('Enregistrer')->form([
            'formation[title]' => 'Formation à modifier',
            'formation[videoId]' => 'video-test-2',
            'formation[publishedAt]' => (new \DateTime('+1 day'))->format('Y-m-d'),
            'formation[playlist]' => (string) $playlist->getId(),
            'formation[description]' => 'Description modifiée',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains(
            'body',
            'La date de publication ne peut pas être postérieure à aujourd\'hui.'
        );

        // reload to verify DB was not updated
        $entityManager->clear();
        $reloadedFormation = $entityManager->getRepository(Formation::class)->find($formationId);

        self::assertNotNull($reloadedFormation);
        self::assertSame(
            $dateBefore,
            $reloadedFormation->getPublishedAt()?->format('Y-m-d')
        );

        // cleanup (use references to avoid "detached entity" errors)
        $entityManager->remove($reloadedFormation);
        $entityManager->remove($entityManager->getReference(Playlist::class, $playlist->getId()));
        $entityManager->remove($entityManager->getReference(User::class, $adminUser->getId()));
        $entityManager->flush();
    }

    private function createAndLoginAdmin(KernelBrowser $client, EntityManagerInterface $entityManager): User
    {
        $user = (new User())
            ->setUsername('admin_test_' . uniqid())
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('test');

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user, 'main');

        return $user;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}
