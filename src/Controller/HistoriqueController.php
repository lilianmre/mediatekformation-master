<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\InscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controleur de l'historique de suivi des formations de l'utilisateur connecté.
 */
class HistoriqueController extends AbstractController {

    /**
     * @var InscriptionRepository
     */
    private $inscriptionRepository;

    const PAGE_HISTORIQUE = "pages/historique.html.twig";

    /**
     * @param InscriptionRepository $inscriptionRepository
     */
    public function __construct(InscriptionRepository $inscriptionRepository) {
        $this->inscriptionRepository = $inscriptionRepository;
    }

    /**
     * Affiche l'historique de toutes les formations suivies par l'utilisateur connecté.
     *
     * @return Response
     */
    #[Route('/historique', name: 'historique')]
    public function index(): Response{
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $inscriptions = $this->inscriptionRepository->findAllByUserOrderByDateInscription($user);

        return $this->render(self::PAGE_HISTORIQUE, [
            'inscriptions' => $inscriptions,
            'termineesUniquement' => false,
        ]);
    }

    /**
     * Affiche l'historique des formations terminées par l'utilisateur connecté,
     * de la plus récemment terminée à la plus ancienne.
     *
     * @return Response
     */
    #[Route('/historique/terminees', name: 'historique.terminees')]
    public function terminees(): Response{
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $inscriptions = $this->inscriptionRepository->findTermineesByUserOrderByDateValidation($user);

        return $this->render(self::PAGE_HISTORIQUE, [
            'inscriptions' => $inscriptions,
            'termineesUniquement' => true,
        ]);
    }
}
