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
    public function index(EntityManagerInterface $entityManager, GameRepository $gameRepository, UserRepository $userRepository): Response
    {


        $games1 = $this->getUser()->getGames1()->getIterator();
        $games2 = $this->getUser()->getGames2()->getIterator();
        $current_games = [];
        foreach ($games1 as $game){
            if($game->getEnded() == null){
                array_push($current_games, $game);
            }

        }

        // RECUP DES AMIS
        $amis = $this->getUser()->getAmis();
        $tab_amis = [];

        foreach ($amis as $index){
            $em = $this->getDoctrine()->getManager(); //on appelle Doctrine
            $query = $em->createQuery( //creation de la requête
                'SELECT u
    FROM App\Entity\User u
    WHERE u.id = :id
    '
            )->setParameter('id', $index);

            $un_ami = $query->getResult();
            array_push($tab_amis, $un_ami[0]);

        }
        //FIN AMIS

        //DEMANDES D'AMIS
        $em = $this->getDoctrine()->getManager(); //on appelle Doctrine
        $query = $em->createQuery( //creation de la requête
            'SELECT u
    FROM App\Entity\User u
    '
        );

        $users = $query->getResult();

        $id = $this->getUser()->getId();
        $mes_amis = $this->getUser()->getAmis();
        $demandes_amis = [];
        foreach ($users as $user){

            if (array_search($id, $user->getAmis()) !== false && array_search($user->getId(), $mes_amis) === false){//si je suis dans ses amis et qu'il n'est pas dans mes amis
                array_push($demandes_amis, $user);

            }
        }


        //INVITS PARTIES

        $parties_invite = [];
        $mes_amis = $this->getUser()->getAmis();


        foreach ($games2 as $game){
            $il_est_dans_mes_amis = false;
            $id_opponent = $game->getUser1()->getId();
            if(array_search($id_opponent, $mes_amis) !== false){
                $il_est_dans_mes_amis = true;
            }
            if($game->getEnded() == null && $game->getRoundEnCours()==1 && count($game->getRounds()[0]->getUser2HandCards())==6 && $game->getRounds()[0]->getUser2Action()['SECRET'] === false){
                if($il_est_dans_mes_amis ===true){
                    array_push($parties_invite, $game);
                }

            }elseif($game->getEnded() == null){
                array_push($current_games, $game);
            }

        }


        //
        $mon_elo = $this->getUser()->getElo();
        $fork1 = $mon_elo - 50;
        $fork2 = $mon_elo + 50;


        $empty_games = $gameRepository->findEmptyGames($fork1, $fork2);

        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'empty_games' => $empty_games,
            'current_games' => $current_games,
            'amis' => $tab_amis,
            'parties_invits' => $parties_invite,
            'demandes_amis'=>$demandes_amis
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

        /*Calcul du nombre de parties gagnées*/
        $partiesGagnees = [];
        $winMercenaire = [];
        $winPoints = [];
        $partiesPerdues = [];

        foreach ($parties as $partie){
            if($partie->getWinner()->getId() == $this->getUser()->getId()){
                if($partie->getTypeVictoire()=='Mercenaires'){
                    array_push($winMercenaire, $partie);
                }else{
                    array_push($winPoints, $partie);
                }
                array_push($partiesGagnees, $partie);
            }else{
                array_push($partiesPerdues, $partie);
            }

        }



    /*calcul adversaires rencontrés*/
        $adversaires = [];
        foreach($parties as $partie){

            if($partie->getUser1()->getId() != $this->getUser()->getId()){//je suis le joueur 2
                if(array_search($partie->getUser1()->getId(), $adversaires)===false){
                    array_push($adversaires, $partie->getUser1()->getId());


                }

            }elseif($partie->getUser2()!==null){
                if($partie->getUser2()->getId() != $this->getUser()->getId()){//je suis le joueur 1
                    if(array_search($partie->getUser2()->getId(), $adversaires)===false){
                        array_push($adversaires, $partie->getUser2()->getId());
                }

            }

            }
        }





        return $this->render('user/profil_et_stats.html.twig', [
            'user' => $this->getUser(),
            'parties' => array_reverse($parties),
            'parties_gagnees'=>$partiesGagnees,
            'win_mercenaire'=>$winMercenaire,
            'win_points'=>$winPoints,
            'parties_perdues'=>$partiesPerdues,
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
        if(isset($_POST['mdp']) && $_POST['mdp']!='' ){
            if($_POST['mdp'] == $_POST['mdp_conf']){
                $user->setPassword(password_hash($_POST['mdp'], PASSWORD_ARGON2I));
            }else{

                return $this->render('user/modifier_profil.html.twig', [
                    'user'=>$user,
                    'erreur' => 'Les deux mots de passe sont différents !'
                ]);
            }

        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('profil_et_stats');
        
    }

    /**
     * @Route ("/ajout-ami/{id}", name="ajout_ami")
     */


    public function ajoutAmi(UserRepository $userRepository, EntityManagerInterface $entityManager, $id){
        $new_ami = $userRepository->find($id);
        $user= $this->getUser();


        $amis = $user->getAmis();

        if(array_search($id, $amis) === false){
            array_push($amis, $new_ami->getId());
            $user->setAmis($amis);

            $entityManager->persist($user);
            $entityManager->flush();

            //ENVOI DE MAIL
            $destinataire=$new_ami->getEmail();
            $subject='Demande d\'ami - The Toria\'s League';
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
            $headers[]='From: iro.games.troyes@gmail.com';
            $message='<h1>Oyez Oyez ! Une nouvelle demande d\'ami !</h1>
            <p>'.$user->getPseudo().' vous a ajouté en ami. Faites en de même pour pouvoir l\'affronter !</p><br>
            <a href="https://mmi19d09.mmi-troyes.fr/TheToriasLeague/login">Ajouter en retour</a>';
            mail($destinataire, $subject, $message, implode("\r\n", $headers));
            //

        }else{
            return $this->render('user/resultats_recherche.html.twig',[
                'message' => 'Vous avez déjà ajouté '.$new_ami->getPseudo().' en ami !',
                'amis'=>$amis,
            ]);
        }

        return $this->render('user/resultats_recherche.html.twig',[
            'message' => 'Vous avez ajouté '.$new_ami->getPseudo().' en ami !',
            'amis'=>$amis,
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