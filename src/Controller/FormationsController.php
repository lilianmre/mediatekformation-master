<?php
namespace App\Controller;

use App\Entity\EtatAvancement;
use App\Entity\Inscription;
use App\Entity\User;
use App\Repository\CategorieRepository;
use App\Repository\FormationRepository;
use App\Repository\InscriptionRepository;
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

    /**
     * @var InscriptionRepository
     */
    private $inscriptionRepository;

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

    /**
     * @param FormationRepository $formationRepository
     * @param CategorieRepository $categorieRepository
     * @param InscriptionRepository $inscriptionRepository
     */
    public function __construct(FormationRepository $formationRepository, CategorieRepository $categorieRepository, InscriptionRepository $inscriptionRepository) {
        $this->formationRepository = $formationRepository;
        $this->categorieRepository= $categorieRepository;
        $this->inscriptionRepository = $inscriptionRepository;
    }

    /**
     * Affiche la liste de toutes les formations.
     *
     * @return Response
     */
    #[Route('/formations', name: 'formations')]
    public function index(): Response{
        $formations = $this->formationRepository->findAll();
        return $this->renderFormations($formations);
    }

    /**
     * Affiche les formations triées selon un champ et un ordre donnés.
     *
     * @param string $champ
     * @param string $ordre
     * @param string $table table jointe si le champ appartient à une entité liée
     * @return Response
     */
    #[Route('/formations/tri/{champ}/{ordre}/{table}', name: 'formations.sort', defaults: ['table' => ''])]
    #[Route('/formations/tri/{champ}/{ordre}', name: 'formations.sort.notable')]
    public function sort($champ, $ordre, $table=""): Response{
        [$champ, $ordre, $table] = $this->validateSortInputs((string) $champ, (string) $ordre, (string) $table);

        $formations = $this->formationRepository->findAllOrderBy($champ, $ordre, $table);
        return $this->renderFormations($formations);
    }

    /**
     * Filtre les formations dont un champ contient la valeur soumise.
     *
     * @param string $champ
     * @param Request $request
     * @param string $table table jointe si le champ appartient à une entité liée
     * @return Response
     */
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
        return $this->renderFormations($formations, $valeur, $table);
    }

    /**
     * Filtre les formations selon l'état de suivi de l'utilisateur connecté
     * (non commencée, en cours, terminée ou non inscrit).
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/formations/recherche-etat', name: 'formations.findbyetat', methods: ['POST'])]
    public function findByEtat(Request $request): Response{
        if (!$this->isCsrfTokenValid('filtre_etat', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide pour le filtre.');
            return $this->redirectToRoute('formations');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('formations');
        }

        $etat = (string) $request->request->get('recherche');
        if ($etat === '') {
            return $this->redirectToRoute('formations');
        }

        if ($etat !== 'non_inscrit' && !EtatAvancement::tryFrom($etat)) {
            throw new BadRequestHttpException('État invalide.');
        }

        $formations = $this->formationRepository->findAllByEtatForUser($user, $etat);
        return $this->renderFormations($formations, $etat, 'etat');
    }

    /**
     * Affiche le détail d'une formation, ainsi que le suivi de l'utilisateur connecté
     * pour cette formation s'il y est inscrit.
     *
     * @param int $id
     * @return Response
     */
    #[Route('/formations/formation/{id}', name: 'formations.showone')]
    public function showOne($id): Response{
        $formation = $this->formationRepository->find($id);

        $inscription = null;
        $user = $this->getUser();
        if ($formation && $user instanceof User) {
            $inscription = $this->inscriptionRepository->findOneByUserAndFormation($user, $formation);
        }

        return $this->render("pages/formation.html.twig", [
            'formation' => $formation,
            'inscription' => $inscription,
        ]);
    }

    /**
     * Inscrit l'utilisateur connecté à la formation (date d'inscription = maintenant,
     * état = non commencée), s'il n'y est pas déjà inscrit.
     *
     * @param int $id
     * @param Request $request
     * @return Response
     */
    #[Route('/formations/formation/{id}/inscription', name: 'formations.inscription', methods: ['POST'])]
    public function inscription($id, Request $request): Response{
        $formation = $this->formationRepository->find($id);
        if (!$formation) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('inscription' . $formation->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('formations.showone', ['id' => $id]);
        }

        if (!$this->inscriptionRepository->findOneByUserAndFormation($user, $formation)) {
            $inscription = new Inscription();
            $inscription->setUser($user);
            $inscription->setFormation($formation);
            $this->inscriptionRepository->add($inscription);
            $this->addFlash('success', 'Inscription confirmée.');
        }

        return $this->redirectToRoute('formations.showone', ['id' => $id]);
    }

    /**
     * Met à jour l'état d'avancement de l'inscription de l'utilisateur connecté
     * pour cette formation (la date de validation est recalculée automatiquement).
     *
     * @param int $id
     * @param Request $request
     * @return Response
     */
    #[Route('/formations/formation/{id}/etat', name: 'formations.etat', methods: ['POST'])]
    public function etat($id, Request $request): Response{
        $formation = $this->formationRepository->find($id);
        if (!$formation) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('etat' . $formation->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('formations.showone', ['id' => $id]);
        }

        $inscription = $this->inscriptionRepository->findOneByUserAndFormation($user, $formation);
        if (!$inscription) {
            throw $this->createNotFoundException();
        }

        $etat = EtatAvancement::tryFrom((string) $request->request->get('etat'));
        if (!$etat) {
            throw new BadRequestHttpException('État invalide.');
        }

        if (!$inscription->getEtat()->peutTransitionnerVers($etat)) {
            $this->addFlash('danger', 'Transition d\'état invalide : la progression doit suivre l\'ordre non commencée -> en cours -> terminée.');
            return $this->redirectToRoute('formations.showone', ['id' => $id]);
        }

        $inscription->setEtat($etat);
        $this->inscriptionRepository->add($inscription);
        $this->addFlash('success', 'Votre suivi a été mis à jour.');

        return $this->redirectToRoute('formations.showone', ['id' => $id]);
    }

    /**
     * Affiche la liste des formations, en y associant l'état d'avancement de
     * l'utilisateur connecté pour chacune d'entre elles.
     *
     * @param array $formations
     * @param mixed $valeur
     * @param string $table
     * @return Response
     */
    private function renderFormations(array $formations, $valeur = null, string $table = ""): Response
    {
        $categories = $this->categorieRepository->findAll();

        $etats = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            foreach ($this->inscriptionRepository->findAllByUser($user) as $inscription) {
                $etats[$inscription->getFormation()->getId()] = $inscription->getEtat();
            }
        }

        return $this->render(self::PAGE_FORMATION, [
            'formations' => $formations,
            'categories' => $categories,
            'valeur' => $valeur,
            'table' => $table,
            'etats' => $etats,
            'etatsAvancement' => EtatAvancement::cases(),
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
