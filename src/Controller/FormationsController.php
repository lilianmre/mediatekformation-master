<?php
namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des formations
 *
 * @author emds
 */
class FormationsController extends AbstractController {

    /**
     * @var FormationRepository
     */
    private $formationRepository;
    
    /**
     * @var CategorieRepository
     */
    private $categorieRepository;

    const PAGE_FORMATION = "pages/formations.html.twig";

    private const SORTABLE = [
        '' => ['title', 'publishedAt'],
        'playlist' => ['name'],
    ];

    private const FILTERABLE = [
        '' => ['title'],
        'playlist' => ['name'],
        'categories' => ['id'],
    ];

    public function __construct(FormationRepository $formationRepository, CategorieRepository $categorieRepository) {
        $this->formationRepository = $formationRepository;
        $this->categorieRepository= $categorieRepository;
    }

    #[Route('/formations', name: 'formations')]
    public function index(): Response{
        $formations = $this->formationRepository->findAll();
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_FORMATION, [
            'formations' => $formations,
            'categories' => $categories
        ]);
    }

    #[Route('/formations/tri/{champ}/{ordre}/{table}', name: 'formations.sort', defaults: ['table' => ''])]
    #[Route('/formations/tri/{champ}/{ordre}', name: 'formations.sort.notable')]
    public function sort($champ, $ordre, $table=""): Response{
        [$champ, $ordre, $table] = $this->validateSortInputs((string) $champ, (string) $ordre, (string) $table);
        
        $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_FORMATION, [
            'formations' => $formations,
            'categories' => $categories
        ]);
    }

    #[Route('/formations/recherche/{champ}/{table}', name: 'formations.findallcontain', defaults: ['table' => ''])]
    #[Route('/formations/recherche/{champ}', name: 'formations.findallcontain.notable')]
    public function findAllContain($champ, Request $request, $table=""): Response{
        [$champ, $table] = $this->validateFilterInputs((string) $champ, (string) $table);

        $token = (string) $request->request->get('_token');
        $tokenId = $this->getFilterTokenId($champ, $table);
        
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide pour le filtre.');
            return $this->redirectToRoute('formations');
        }

        $valeur = $request->get("recherche");
        $formations = $this->formationRepository->findByContainValue($champ, $valeur, $table);
        $categories = $this->categorieRepository->findAll();
        return $this->render(self::PAGE_FORMATION, [
            'formations' => $formations,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table
        ]);
    }

    #[Route('/formations/formation/{id}', name: 'formations.showone')]
    public function showOne($id): Response{
        $formation = $this->formationRepository->find($id);
        return $this->render("pages/formation.html.twig", [
            'formation' => $formation
        ]);
    }

    /**
     * Valide les entrées de tri
     */
    private function validateSortInputs(string $champ, string $ordre, string $table): array
    {
        $ordre = strtoupper($ordre);

        if (!in_array($ordre, ['ASC', 'DESC'], true)) {
            throw new BadRequestHttpException('Ordre de tri invalide.');
        }

        if (!isset(self::SORTABLE[$table]) ||
            !in_array($champ, self::SORTABLE[$table], true)) {
            throw new BadRequestHttpException('Champ de tri invalide.');
        }

        return [$champ, $ordre, $table];
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
