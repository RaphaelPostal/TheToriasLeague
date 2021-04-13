<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Game;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/accueil", name="accueil")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('The Toria\'s League');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Parties', 'fas fa-users', Game::class);
        yield MenuItem::linkToRoute('Statistiques', 'fa fa-tag', 'back_stats');
    }

    /**
     * @Route("/stats", name="back_stats")
     */
    public function stats(): Response
    {
        return $this->render('stats/index.html.twig');
    }
}