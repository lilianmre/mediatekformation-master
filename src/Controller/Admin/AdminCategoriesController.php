<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminCategoriesController extends AbstractController
{
    private const ADMIN_PAGE_CATEGORIES = 'admin/pages/categories.html.twig';

    private CategorieRepository $categorieRepository;

    public function __construct(CategorieRepository $categorieRepository)
    {
        $this->categorieRepository = $categorieRepository;
    }

    #[Route('/admin/categories', name: 'admin_categories', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categorieRepository->add($categorie);
            $this->addFlash('success', 'La catégorie a été ajoutée.');

            return $this->redirectToRoute('admin_categories');
        }

        $categories = $this->categorieRepository->findAll();

        return $this->render(self::ADMIN_PAGE_CATEGORIES, [
            'categories' => $categories,
            'categorieForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin_categories.delete', methods: ['POST'])]
    public function delete(Categorie $categorie, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_categorie_' . $categorie->getId(), $token)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($categorie->getFormations()->count() > 0) {
            $this->addFlash('danger', 'Impossible de supprimer une catégorie qui contient des formations.');

            return $this->redirectToRoute('admin_categories');
        }

        $this->categorieRepository->remove($categorie);
        $this->addFlash('success', 'La catégorie a été supprimée.');

        return $this->redirectToRoute('admin_categories');
    }
}
