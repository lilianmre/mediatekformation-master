<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPlaylistController extends AbstractController
{
    #[Route('/admin/playlist', name: 'admin_playlist')]
    public function playlist(): Response
    {
        return $this->render('pages/admin/playlist.html.twig', [
            'titre' => 'Playlist',
        ]);
    }

}
