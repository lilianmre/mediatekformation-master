<?php
namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur des formations
 *
 * @author emds
 */

class AdminFormationsController extends AbstractController {

    private const ADMIN_PAGE_FORMATION = 'admin/pages/formations.html.twig';
    private const ADMIN_PAGE_FORM = 'admin/pages/formation/form.html.twig';
    private const FILTER_CONFIG = [
        '' => ['filtre_title'],
        'playlist' => ['filtre_name'],
        'categories' => ['filtre_id'],
    ];

    /**
     * @var FormationRepository
     */
    private $formationRepository;

    /**
     * @var CategorieRepository
     */
    private $categorieRepository;

    public function __construct(FormationRepository $formationRepository, CategorieRepository $categorieRepository) {
        $this->formationRepository = $formationRepository;
        $this->categorieRepository = $categorieRepository;
    }

    #[Route('/admin/formations', name: 'admin_formations')]
    public function index(): Response {
        $formations = $this->formationRepository->findAll();
        return $this->renderAdminFormations($formations);
    }

    #[Route('/admin/formations/tri/{champ}/{ordre}/{table}', name: 'admin_formations.sort', defaults: ['table' => ''])]
    #[Route('/admin/formations/tri/{champ}/{ordre}', name: 'admin_formations.sort.notable')]
    public function sort($champ, $ordre, $table = ""): Response {
        [$champ, $ordre, $table] = $this->validateSortInputs((string) $champ, (string) $ordre, (string) $table);
        $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
        return $this->renderAdminFormations($formations);
    }

    #[Route('/admin/formations/recherche/{champ}/{table}', name: 'admin_formations.findallcontain', defaults: ['table' => ''])]
    #[Route('/admin/formations/recherche/{champ}', name: 'admin_formations.findallcontain.notable')]
    public function findAllContain($champ, Request $request, $table = ""): Response {
        [$champ, $table] = $this->validateFilterInputs((string) $champ, (string) $table);

        $token = (string) $request->request->get('_token');
        $tokenId = $this->getFilterTokenId($champ, $table);
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide pour le filtre.');

            return $this->redirectToRoute('admin_formations');
        }

        $valeur = $request->get("recherche");
        $formations = $this->formationRepository->findByContainValue($champ, $valeur, $table);
        return $this->renderAdminFormations($formations, $valeur, $table);
    }

    #[Route('/admin/formations/add', name: 'admin_formations.add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response {
        $formation = new Formation();

        return $this->handleForm($formation, $request, 'Formation ajoutée.');
    }

    #[Route('/admin/formations/delete/{id}', name: 'admin_formations.delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response {
        if (!$this->isCsrfTokenValid('delete_formation_'.$id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_formations');
        }

        $formation = $this->formationRepository->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $playlist = $formation->getPlaylist();
        if ($playlist) {
            $playlist->removeFormation($formation);
        }

        $this->formationRepository->remove($formation);
        $this->addFlash('success', 'Formation supprimée.');

        return $this->redirectToRoute('admin_formations');
    }

    #[Route('/admin/formations/{id}/edit', name: 'admin_formations.edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response {
        $formation = $this->formationRepository->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        return $this->handleForm($formation, $request, 'Formation modifiée.');
    }

    private function renderAdminFormations(array $formations, $valeur = null, string $table = ""): Response {
        $categories = $this->categorieRepository->findAll();

        return $this->render(self::ADMIN_PAGE_FORMATION, [
            'formations' => $formations,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table
        ]);
    }

    private function handleForm(Formation $formation, Request $request, string $successMessage): Response {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->formationRepository->add($formation);
            $this->addFlash('success', $successMessage);

            return $this->redirectToRoute('admin_formations');
        }

        return $this->render(self::ADMIN_PAGE_FORM, [
            'formationForm' => $form->createView(),
            'formation' => $formation,
            'isEdit' => null !== $formation->getId(),
        ]);
    }

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

    private function validateFilterInputs(string $champ, string $table): array
    {
        if (
            !isset(self::FILTER_CONFIG[$table]) ||
            !in_array($champ, self::FILTERABLE[$table], true)
        ) {
            throw new BadRequestHttpException('Filtre invalide.');
        }

        return [$champ, $table];
    }

    private function getFilterTokenId(string $champ, string $table): string
    {
    if (!isset(self::FILTER_CONFIG[$table][$champ])) {
        throw new BadRequestHttpException('Token de filtre introuvable.');
    }

    return self::FILTER_CONFIG[$table][$champ];
    }

}
