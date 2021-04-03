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
        $games1 = $this->getUser()->getGames1()->getIterator();
        $games2 = $this->getUser()->getGames2()->getIterator();
        $current_games = [];
        foreach ($games1 as $game){
            if($game->getEnded() == null){
                array_push($current_games, $game);
            }

        }

        foreach ($games2 as $game){
            if($game->getEnded() == null){
                array_push($current_games, $game);
            }

        }



        $empty_games = $gameRepository->findEmptyGames();
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'empty_games' => $empty_games,
            'current_games' => $current_games
        ]);
    }

    /**
     * @Route("/stats", name="profil_et_stats")
     */
    public function profilEtStats(EntityManagerInterface $entityManager, GameRepository $gameRepository): Response
    {
        //$parties1 = $this->getUser()->getGames1()->getIterator();
        //$parties2 = $this->getUser()->getGames2()->getIterator();



        $em = $this->getDoctrine()->getManager(); //on appelle Doctrine
        $query = $em->createQuery( //creation de la requête
            'SELECT g
    FROM App\Entity\Game g
    JOIN g.user2 u2
    JOIN g.user1 u1
    WHERE u2.id = :id
    AND g.ended IS NOT NULL
    OR u1.id = :id
    AND g.ended IS NOT NULL
    '
        )->setParameter('id', $this->getUser()->getId());

        $parties = $query->getResult();


    /*calcul adversaires rencontrés*/
        $adversaires = [];
        foreach($parties as $partie){

            if($partie->getUser1()->getId() != $this->getUser()->getId()){
                if(array_search($partie->getUser1()->getPseudo(), $adversaires)==null){
                    array_push($adversaires, $partie->getUser1()->getPseudo());
                }

            }elseif($partie->getUser2()!=null){
                if($partie->getUser2()->getId() != $this->getUser()->getId()){
                    if(array_search($partie->getUser2()->getPseudo(), $adversaires)==null){
                        array_push($adversaires, $partie->getUser2()->getPseudo());
                }

            }

            }
        }



        return $this->render('user/profil_et_stats.html.twig', [
            'user' => $this->getUser(),
            'parties' => array_reverse($parties),
            'adversaires'=>$adversaires



        ]);
    }


    /**
     * @Route ("/modifier", name="modifier")
     */

    public function modifier():Response{
        $user = $this->getUser();

        return $this->render('user/modifier_profil.html.twig',[
            'user'=>$user
        ]);

    }

    /**
     * @Route ("/valide-modifications", name="valide_modifs")
     */

    public function valideModifs(EntityManagerInterface $entityManager):Response{

        $user = $this->getUser();

        $user->setFirstname($_POST['prenom']);
        $user->setLastname($_POST['nom']);
        $user->setPseudo($_POST['pseudo']);
        if(isset($_POST['photo'])){
            $user->setPhoto($_POST['photo'].'.png');
        }
        if(isset($_POST['mdp']) && $_POST['mdp']!=''){
            $user->setPassword(password_hash($_POST['mdp'], PASSWORD_ARGON2I));
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('profil_et_stats');
        
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