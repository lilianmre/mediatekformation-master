<?php
namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Description of PlaylistsController
 *
 * @author emds
 */
class PlaylistsController extends AbstractController {
    
    /**
     * @var PlaylistRepository
     */
    private $playlistRepository;
    
    /**
     * @var FormationRepository
     */
    private $formationRepository;
    
    /**
     * @var CategorieRepository
     */
    private $categorieRepository;
    
    const PAGE_PLAYLIST = "pages/playlists.html.twig";

    private const SORTABLE = ['name', 'nbCategories'];

    private const FILTERABLE = [
        '' => ['name'],
        'categories' => ['id'],
    ];

    public function __construct(PlaylistRepository $playlistRepository,
            CategorieRepository $categorieRepository,
            FormationRepository $formationRespository) {
        $this->playlistRepository = $playlistRepository;
        $this->categorieRepository = $categorieRepository;
        $this->formationRepository = $formationRespository;
    }
    
    /**
     * @Route("/playlists", name="playlists")
     * @return Response
     */
    #[Route('/playlists', name: 'playlists')]
    public function index(): Response{
        $playlists = $this->playlistRepository->findAllOrderByName('ASC');
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_PLAYLIST, [
            'playlists' => $playlists,
            'categories' => $categories
        ]);
    }

    #[Route('/playlists/tri/{champ}/{ordre}', name: 'playlists.sort')]
    public function sort($champ, $ordre): Response{
        [$champ, $ordre] = $this->validateSortInputs((string) $champ, (string) $ordre);

        if($champ == "name"){
            $playlists = $this->playlistRepository->findAllOrderByName($ordre);
        } else {
            $playlists = $this->playlistRepository->findAllOrderByFormationCount($ordre);
        }
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_PLAYLIST, [
            'playlists' => $playlists,
            'categories' => $categories
        ]);
    }

    #[Route('/playlists/recherche/{champ}/{table}', name: 'playlists.findallcontain', defaults: ['table' => ''])]
    #[Route('/playlists/recherche/{champ}', name: 'playlists.findallcontain.notable')]
    public function findAllContain($champ, Request $request, $table=""): Response{
        [$champ, $table] = $this->validateFilterInputs((string) $champ, (string) $table);

        $token = (string) $request->request->get('_token');
        $tokenId = $this->getFilterTokenId($champ, $table);
        
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide pour le filtre.');
            return $this->redirectToRoute('playlists');
        }

        $valeur = $request->get("recherche");
        $playlists = $this->playlistRepository->findByContainValue($champ, $valeur, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_PLAYLIST, [
            'playlists' => $playlists,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table
        ]);
    }

    #[Route('/playlists/playlist/{id}', name: 'playlists.showone')]
    public function showOne($id): Response{
        $playlist = $this->playlistRepository->find($id);
        $playlistCategories = $this->categorieRepository->findAllForOnePlaylist($id);
        $playlistFormations = $this->formationRepository->findAllForOnePlaylist($id);
        return $this->render("pages/playlist.html.twig", [
            'playlist' => $playlist,
            'playlistcategories' => $playlistCategories,
            'playlistformations' => $playlistFormations
        ]);
    }

    /**
     * Valide les entrées de tri
     */
    private function validateSortInputs(string $champ, string $ordre): array
    {
        $ordre = strtoupper($ordre);

        if (!in_array($ordre, ['ASC', 'DESC'], true)) {
            throw new BadRequestHttpException('Ordre de tri invalide.');
        }

        if (!in_array($champ, self::SORTABLE, true)) {
            throw new BadRequestHttpException('Champ de tri invalide.');
        }

        return [$champ, $ordre];
    }

    /**
     * Valide les entrées de filtre
     */
    private function validateFilterInputs(string $champ, string $table): array
    {
        if (
            !isset(self::FILTERABLE[$table]) ||
            !in_array($champ, self::FILTERABLE[$table], true)
        ) {
            throw new BadRequestHttpException('Filtre invalide.');
        }

        return [$champ, $table];
    }

    /**
     * Génère l'identifiant du token CSRF pour un filtre donné
     */
    private function getFilterTokenId(string $champ, string $table): string
    {
        if ($table === '') {
            return 'filtre_' . $champ;
        }
        return 'filtre_' . $table . '_' . $champ;
    }
    
}
