<?php

namespace App\Controller\Admin;

use App\Entity\Playlist;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPlaylistsController extends AbstractController
{
    private const ADMIN_PAGE_PLAYLIST = 'admin/pages/playlists.html.twig';
    private const ADMIN_PAGE_FORM = 'admin/pages/playlist/form.html.twig';
    private const SORTABLE_CHAMPS = ['name', 'nbFormations'];
    private const FILTERABLE_CHAMPS = ['name'];
    private const ORDERS = ['ASC', 'DESC'];

    private PlaylistRepository $playlistRepository;

    public function __construct(PlaylistRepository $playlistRepository)
    {
        $this->playlistRepository = $playlistRepository;
    }

    #[Route('/admin/playlists', name: 'admin_playlists', methods: ['GET'])]
    public function index(): Response
    {
        $playlists = $this->playlistRepository->findAllOrderByName('ASC');

        return $this->render(self::ADMIN_PAGE_PLAYLIST, [
            'playlists' => $playlists,
        ]);
    }

    #[Route('/admin/playlists/tri/{champ}/{ordre}', name: 'admin_playlists.sort')]
    public function sort(string $champ, string $ordre): Response
    {
        $champ = $this->validateSortChamp($champ);
        $ordre = $this->validateOrder($ordre);

        if ('name' === $champ) {
            $playlists = $this->playlistRepository->findAllOrderByName($ordre);
        } elseif ('nbFormations' === $champ) {
            $playlists = $this->playlistRepository->findAllOrderByFormationCount($ordre);
        } else {
            throw new BadRequestHttpException('Champ de tri invalide.');
        }

        return $this->render(self::ADMIN_PAGE_PLAYLIST, [
            'playlists' => $playlists,
        ]);
    }

    #[Route('/admin/playlists/recherche/{champ}', name: 'admin_playlists.findallcontain', methods: ['POST'])]
    public function findAllContain(string $champ, Request $request): Response
    {
        $champ = $this->validateFilterChamp($champ);

        $token = (string) $request->request->get('_token');
        $tokenId = 'filtre_' . $champ;
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide pour le filtre.');

            return $this->redirectToRoute('admin_playlists');
        }

        $valeur = (string) $request->get('recherche');
        $playlists = $this->playlistRepository->findByContainValue($champ, $valeur);

        return $this->render(self::ADMIN_PAGE_PLAYLIST, [
            'playlists' => $playlists,
            'valeur' => $valeur,
            'champ' => $champ,
        ]);
    }

    #[Route('/admin/playlists/add', name: 'admin_playlists.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $playlist = new Playlist();

        return $this->handleForm($playlist, $request, 'Playlist ajoutée.');
    }

    #[Route('/admin/playlists/{id}/edit', name: 'admin_playlists.edit', methods: ['GET', 'POST'])]
    public function edit(Playlist $playlist, Request $request): Response
    {
        return $this->handleForm($playlist, $request, 'Playlist modifiée.');
    }

    #[Route('/admin/playlists/{id}/delete', name: 'admin_playlists.delete', methods: ['POST'])]
    public function delete(Playlist $playlist, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_playlist_' . $playlist->getId(), $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($playlist->getFormations()->count() > 0) {
            $this->addFlash('danger', 'Impossible de supprimer une playlist qui contient des formations.');

            return $this->redirectToRoute('admin_playlists');
        }

        $this->playlistRepository->remove($playlist);
        $this->addFlash('success', 'La playlist a été supprimée.');

        return $this->redirectToRoute('admin_playlists');
    }

    private function handleForm(Playlist $playlist, Request $request, string $successMessage): Response
    {
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->playlistRepository->add($playlist);
            $this->addFlash('success', $successMessage);

            return $this->redirectToRoute('admin_playlists');
        }

        return $this->render(self::ADMIN_PAGE_FORM, [
            'playlistForm' => $form->createView(),
            'playlist' => $playlist,
            'isEdit' => null !== $playlist->getId(),
            'formations' => $playlist->getFormations(),
        ]);
    }

    private function validateSortChamp(string $champ): string
    {
        if (!in_array($champ, self::SORTABLE_CHAMPS, true)) {
            throw new BadRequestHttpException('Champ de tri invalide.');
        }

        return $champ;
    }

    private function validateFilterChamp(string $champ): string
    {
        if (!in_array($champ, self::FILTERABLE_CHAMPS, true)) {
            throw new BadRequestHttpException('Champ de filtre invalide.');
        }

        return $champ;
    }
    
    private function validateOrder(string $ordre): string
    {
        $ordre = strtoupper($ordre);

        if (!in_array($ordre, self::ORDERS, true)) {
            throw new BadRequestHttpException('Ordre de tri invalide.');
        }

        return $ordre;
    }
}
