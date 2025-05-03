<?php

namespace App\Controller;

use App\Entity\Page;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/page')]
class PageController extends AbstractController
{
    #[Route('/{slug}', name: 'app_page_show')]
    public function show(string $slug, PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBySlug($slug);
        
        if (!$page) {
            throw $this->createNotFoundException('La page demandée n\'existe pas.');
        }
        
        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}
