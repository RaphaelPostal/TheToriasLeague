<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
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
     * @Route("/accueil", name="accueil_admin")
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
    public function stats(GameRepository $gameRepository, UserRepository $userRepository): Response
    {
        $end_games = $gameRepository->findAllEnded();
        $tab_inter = [];
        foreach ($end_games as $game){
            $date1 = $game->getCreated();
            $date2 = $game->getEnded();

            $interval = $date1->diff($date2);

            array_push($tab_inter, $interval);
        }

        $tab_sec = [];
        $tab_min = [];

        foreach ($tab_inter as $inter){
            array_push($tab_sec, $inter->s);
            array_push($tab_min, $inter->i);


        }

        $moy_min = array_sum($tab_min)/count($tab_min);
        $moy_sec = array_sum($tab_sec)/count($tab_sec);

        $duree_moy = [round($moy_min, 0) , round($moy_sec,0)];

        $top10 = $userRepository->findTop10();



        $victoiresM = $gameRepository->findVictoiresM();
        $nb_victoiresM = count($victoiresM);
        $victoiresP = $gameRepository->findVictoiresP();
        $nb_victoiresP = count($victoiresP);
        $datas = [$nb_victoiresM, $nb_victoiresP];
        $datas2 = json_encode($datas);
        return $this->render('stats/index.html.twig', [
            'datas'=>$datas2,
            'duree_moy'=>$duree_moy,
            'top10'=>$top10

        ]);
    }
}