<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @Route("/profil")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_profil")
     */
    public function index(EntityManagerInterface $entityManager, GameRepository $gameRepository): Response
    {
        /*
        $em = $this->getDoctrine()->getManager(); //on appelle Doctrine
        $query = $em->createQuery( //creation de la requête
            'SELECT g
    FROM App\Entity\Game g
    WHERE g.user2 IS NULL
    '
        );

        $empty_games = $query->getResult();
        */
        $empty_games = $gameRepository->findEmptyGames();
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'empty_games' => $empty_games,
        ]);
    }

    /**
     * @Route("/stats", name="profil_et_stats")
     */
    public function profilEtStats(EntityManagerInterface $entityManager, GameRepository $gameRepository): Response
    {
        $parties1 = $this->getUser()->getGames1()->getIterator();
        $parties2 = $this->getUser()->getGames2()->getIterator();
        $parties = [];
        foreach ($parties1 as $value){

            array_push($parties, $value);
        }
        foreach ($parties2 as $value){
            array_push($parties, $value);
        }





    /*calcul adversaires rencontrés*/
        $adversaires = [];
        foreach($parties as $partie){

            if($partie->getUser1()->getId() != $this->getUser()->getId()){
                if(array_search($partie->getUser1()->getPseudo(), $adversaires)==null){
                    array_push($adversaires, $partie->getUser1()->getPseudo());
                }

            }elseif($partie->getUser2()->getId() != $this->getUser()->getId()){
                if(array_search($partie->getUser2()->getPseudo(), $adversaires)==null){
                    array_push($adversaires, $partie->getUser2()->getPseudo());
                }

            }
        }



        return $this->render('user/profil_et_stats.html.twig', [
            'user' => $this->getUser(),
            'parties' => $parties,
            'adversaires'=>array_flip($adversaires)



        ]);
    }

    /**
     * @Route("/deconnexion", name="deconnexion")
     */
    public function deconnexion(EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $user -> setDerniereConnexion(new \DateTime());
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_logout');
    }


}