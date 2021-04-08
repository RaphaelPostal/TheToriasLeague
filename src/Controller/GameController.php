<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Round;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jeu")
 */
class GameController extends AbstractController
{
    /**
     * @Route("/new-game", name="new_game")
     */
    public function newGame(
        UserRepository $userRepository
    ): Response {
        $users = $userRepository->findAll();

        return $this->render('game/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/create-game", name="create_game")
     */
    public function createGame(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CardRepository $cardRepository
    ): Response {
        $user1 = $this->getUser();

        $user2 = $userRepository->find($request->request->get('user2'));

        //les 2 joueurs n'ont pas pioché
        $user1->setDejaPioche(0);
        if($user2 != null){
            $user2->setDejaPioche(0);
        }


        if ($user1 !== $user2) {
            $game = new Game();
            $game->setUser1($user1);
            $game->setUser2($user2);
            $entityManager->persist($user1, $user2);
            $game->setCreated(new \DateTime('now'));

            $entityManager->persist($game);

            $round = new Round();
            $round->setGame($game);
            $round->setCreated(new \DateTime('now'));
            $round->setRoundNumber(1);

            $cards = $cardRepository->findAll();
            $tCards = [];
            foreach ($cards as $card) {
                $tCards[$card->getId()] = $card;
            }
            shuffle($tCards);
            $carte = array_pop($tCards);
            $round->setRemovedCard($carte->getId());

            $tMainJ1 = [];
            $tMainJ2 = [];
            for ($i = 0; $i < 6; $i++) {
                //on distribue 6 cartes aux deux joueurs
                $carte = array_pop($tCards);
                $tMainJ1[] = $carte->getId();
                $carte = array_pop($tCards);
                $tMainJ2[] = $carte->getId();
            }
            $round->setUser1HandCards($tMainJ1);
            $round->setUser2HandCards($tMainJ2);

            $tPioche = [];

            foreach ($tCards as $card) {
                $carte = array_pop($tCards);
                $tPioche[] = $carte->getId();
            }
            $round->setPioche($tPioche);
            $round->setUser1Action([
                'SECRET' => false,
                'DEPOT' => [],
                'OFFRE' => ["CartesChoisiesMoi"=>[], "CartesChoisiesAdversaire"=>[]],
                'ECHANGE' => []
            ]);

            $round->setUser2Action([
                'SECRET' => false,
                'DEPOT' => [],
                'OFFRE' => ["CartesChoisiesMoi"=>[], "CartesChoisiesAdversaire"=>[]],
                'ECHANGE' => []
            ]);

            $round->setBoard([
                'EMPL1' => ['N'],
                'EMPL2' => ['N'],
                'EMPL3' => ['N'],
                'EMPL4' => ['N'],
                'EMPL5' => ['N'],
                'EMPL6' => ['N'],
                'EMPL7' => ['N']
            ]);

            $round->setUser1BoardCards([
                'KRULMO' => [],
                'GANORMO' => [],
                'RASDAR' => [],
                'ARCADIA' => [],
                'ASTRALIA' => [],
                'THARUK' => [],
                'SOFIA' => [],

            ]);

            $round->setUser2BoardCards([
                'KRULMO' => [],
                'GANORMO' => [],
                'RASDAR' => [],
                'ARCADIA' => [],
                'ASTRALIA' => [],
                'THARUK' => [],
                'SOFIA' => [],

            ]);

            $entityManager->persist($round);
            $entityManager->flush();

            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()

            ]);
        } else {
            return $this->redirectToRoute('new_game');
        }
    }

    /**
     * @Route("/show-game/{game}", name="show_game")
     */
    public function showGame(
        Game $game,
        Round $round
    ): Response {
        if ($this->getUser()->getId() === $game->getUser1()->getId() || $this->getUser()->getId() === $game->getUser2()->getId()){

            return $this->render('game/show_game.html.twig', [
                'game' => $game,
                'round' => $round
            ]);
        }else{
            return $this->redirectToRoute('user_profil');
        }

    }



    /**
     * @param Game $game
     * @route("/refresh/{game}", name="refresh_plateau_game")
     */
    public function refreshPlateauGame(CardRepository $cardRepository, Game $game)
    {
        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser1Action();
            $moi['board'] = $game->getRounds()[0]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser2BoardCards();

        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {

            $moi['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser2Action();
            $moi['board'] = $game->getRounds()[0]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser1BoardCards();
        } else {
            //redirection... je ne suis pas l'un des deux joueurs ???? PAS OBLIGATOIRE ????
            return $this->redirectToRoute('user_profil');
        }

        return $this->render('game/plateau_game.html.twig', [
            'game' => $game,
            'round' => $game->getRounds()[0],
            'cards' => $tCards,
            'moi' => $moi,
            'adversaire' => $adversaire
        ]);
    }


    /**
     * @Route("/pioche/{game}", name="pioche")
     */
    public function pioche(
        EntityManagerInterface $entityManager,
        Request $request, Game $game, CardRepository $cardRepository):Response{



        $user = $this->getUser();

        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId())
        {
            $joueur = 1;
        } elseif ($game->getUser2()->getId() === $user->getId()) {
            $joueur = 2;
        } else {
            /// On a un problème... On pourrait rediriger vers une page d'erreur.
        }

            $pioche = $round->getPioche();
        var_dump($pioche);
        //tester s'il a pas déjà pioché
        $session = $this->get('session');
        if($user->getDejaPioche()==0){

            if ($joueur === 1) {


                $main = $round->getUser1HandCards();


                $id_carte_tiree= array_pop($pioche);

                $carte_tiree = $cardRepository->find($id_carte_tiree);
                $main[]= $carte_tiree->getId();
                $round->setUser1HandCards($main);
                $round->setPioche($pioche);
            }elseif ($joueur === 2){

                $main = $round->getUser2HandCards();
                $id_carte_tiree= array_pop($pioche);

                $carte_tiree = $cardRepository->find($id_carte_tiree);
                $main[]= $carte_tiree->getId();
                $round->setUser2HandCards($main);
                $round->setPioche($pioche);

            }

            $session->set('pioche', '0');

            $user->setDejaPioche(1);
            $entityManager->persist($round, $user);
            $entityManager->flush();
            $data=["your_turn"=>true];

            return $this->json($data);
        }else{

            $session->set('pioche', '1');
            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()

            ]);
        }

    }


    /**
     * @Route("/action-game/{game}", name="action_game")
     */
    public function actionGame(
        EntityManagerInterface $entityManager,
        Request $request, Game $game){


        $action = $request->request->get('action');
        $user = $this->getUser();
        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId())
        {
            $joueur = 1;
        } elseif ($game->getUser2()->getId() === $user->getId()) {
            $joueur = 2;
        } else {
            /// On a un problème... On pourrait rediriger vers une page d'erreur.
        }

        switch ($action) {
            case 'secret':
                $carte = $request->request->get('carte');

                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser2HandCards($main);
                }
                break;


            case 'depot':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    array_push($actions['DEPOT'], $carte1);
                    array_push($actions['DEPOT'], $carte2);

                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){

                    $actions = $round->getUser2Action(); //un tableau...
                    array_push($actions['DEPOT'], $carte1);
                    array_push($actions['DEPOT'], $carte2);
                    $round->setUser2Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    $round->setUser2HandCards($main);

                }
                break;


            case 'offre':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                $carte3 = $request->request->get('carte3');

                if ($joueur === 1) {
                    $round->setUser1ActionEnCours('OFFRE');
                    $game->getUser2()->setDejaPioche(1);
                    $actions = $round->getUser1Action(); //un tableau...
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte1);
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte2);
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte3);

                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    $indexCarte3 = array_search($carte3, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    unset($main[$indexCarte3]);
                    $round->setUser1HandCards($main);

                }elseif ($joueur === 2){

                    $round->setUser2ActionEnCours('OFFRE');
                    $game->getUser1()->setDejaPioche(1);
                    $actions = $round->getUser2Action(); //un tableau...
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte1);
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte2);
                    array_push($actions['OFFRE']['CartesChoisiesMoi'], $carte3);

                    $round->setUser2Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    $indexCarte3 = array_search($carte3, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    unset($main[$indexCarte3]);
                    $round->setUser2HandCards($main);


                }

                break;



            case 'offre_adv':
                if ($joueur === 1) { // si c'est le joueur 1 qui choisit sa carte parmis les 3
                    $carteChoisie = $request->request->get('carteChoisie');
                    $actions = $round->getUser2Action(); //un tableau...
                    array_push($actions['OFFRE']['CartesChoisiesAdversaire'], $carteChoisie);
                    $indexCarteChoisie = array_search($carteChoisie, $actions['OFFRE']['CartesChoisiesMoi']);

                    unset($actions['OFFRE']['CartesChoisiesMoi'][$indexCarteChoisie]);

                    $round->setUser2Action($actions); //je mets à jour le tableau dans bdd
                    $cartesRestantes = $actions['OFFRE']['CartesChoisiesMoi'];

                    $user2BoardCards = $round->getUser2BoardCards();
                    $user1BoardCards = $round->getUser1BoardCards();

                    //REPARTITION POUR LE JOUEUR 2 avec les 2 cartes restantes
                    foreach($cartesRestantes as $carte){

                        if($carte == 1 || $carte == 2){
                            array_push($user2BoardCards['KRULMO'], $carte);
                        }elseif ($carte == 3 || $carte == 4){
                            array_push($user2BoardCards['GANORMO'], $carte);
                        }elseif($carte == 5 || $carte == 6){
                            array_push($user2BoardCards['RASDAR'], $carte);
                        }elseif($carte == 7 || $carte == 8 || $carte == 9){
                            array_push($user2BoardCards['ARCADIA'], $carte);
                        }elseif ($carte == 10 || $carte == 11 || $carte == 12){
                            array_push($user2BoardCards['ASTRALIA'], $carte);
                        }elseif($carte == 13 || $carte == 14 || $carte == 15 || $carte == 16){
                            array_push($user2BoardCards['THARUK'], $carte);
                        }elseif($carte == 17 || $carte == 18 || $carte == 19 || $carte == 20 || $carte == 21){
                            array_push($user2BoardCards['SOFIA'], $carte);
                        }


                    }

                    //REPARTITION POUR LE JOUEUR 1 avec sa carte choisie
                    if($carteChoisie == 1 || $carteChoisie == 2){
                        array_push($user1BoardCards['KRULMO'], $carteChoisie);
                    }elseif ($carteChoisie == 3 || $carteChoisie == 4){
                        array_push($user1BoardCards['GANORMO'], $carteChoisie);
                    }elseif($carteChoisie == 5 || $carteChoisie == 6){
                        array_push($user1BoardCards['RASDAR'], $carteChoisie);
                    }elseif($carteChoisie == 7 || $carteChoisie == 8 || $carteChoisie == 9){
                        array_push($user1BoardCards['ARCADIA'], $carteChoisie);
                    }elseif ($carteChoisie == 10 || $carteChoisie == 11 || $carteChoisie == 12){
                        array_push($user1BoardCards['ASTRALIA'], $carteChoisie);
                    }elseif($carteChoisie == 13 || $carteChoisie == 14 || $carteChoisie == 15 || $carteChoisie == 16){
                        array_push($user1BoardCards['THARUK'], $carteChoisie);
                    }elseif($carteChoisie == 17 || $carteChoisie == 18 || $carteChoisie == 19 || $carteChoisie == 20 || $carteChoisie == 21){
                        array_push($user1BoardCards['SOFIA'], $carteChoisie);
                    }

                    $round->setUser2BoardCards($user2BoardCards); //les 2 cartes restantes
                    $round->setUser1BoardCards($user1BoardCards); //la carte choisie
                    $round->setUser2ActionEnCours(null);
                    $game->getUser1()->setDejaPioche(0);


                }elseif ($joueur === 2){

                    $carteChoisie = $request->request->get('carteChoisie');
                    $actions = $round->getUser1Action(); //un tableau...
                    array_push($actions['OFFRE']['CartesChoisiesAdversaire'], $carteChoisie);
                    $indexCarteChoisie = array_search($carteChoisie, $actions['OFFRE']['CartesChoisiesMoi']);

                    unset($actions['OFFRE']['CartesChoisiesMoi'][$indexCarteChoisie]);

                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    $cartesRestantes = $actions['OFFRE']['CartesChoisiesMoi'];

                    $user1BoardCards = $round->getUser1BoardCards();
                    $user2BoardCards = $round->getUser2BoardCards();

                    //REPARTITION POUR LE JOUEUR 1 avec les 2 cartes restantes
                    foreach($cartesRestantes as $carte){

                        if($carte == 1 || $carte == 2){
                            array_push($user1BoardCards['KRULMO'], $carte);
                        }elseif ($carte == 3 || $carte == 4){
                            array_push($user1BoardCards['GANORMO'], $carte);
                        }elseif($carte == 5 || $carte == 6){
                            array_push($user1BoardCards['RASDAR'], $carte);
                        }elseif($carte == 7 || $carte == 8 || $carte == 9){
                            array_push($user1BoardCards['ARCADIA'], $carte);
                        }elseif ($carte == 10 || $carte == 11 || $carte == 12){
                            array_push($user1BoardCards['ASTRALIA'], $carte);
                        }elseif($carte == 13 || $carte == 14 || $carte == 15 || $carte == 16){
                            array_push($user1BoardCards['THARUK'], $carte);
                        }elseif($carte == 17 || $carte == 18 || $carte == 19 || $carte == 20 || $carte == 21){
                            array_push($user1BoardCards['SOFIA'], $carte);
                        }


                    }

                    //REPARTITION POUR LE JOUEUR 2 avec sa carte choisie
                    if($carteChoisie == 1 || $carteChoisie == 2){
                        array_push($user2BoardCards['KRULMO'], $carteChoisie);
                    }elseif ($carteChoisie == 3 || $carteChoisie == 4){
                        array_push($user2BoardCards['GANORMO'], $carteChoisie);
                    }elseif($carteChoisie == 5 || $carteChoisie == 6){
                        array_push($user2BoardCards['RASDAR'], $carteChoisie);
                    }elseif($carteChoisie == 7 || $carteChoisie == 8 || $carteChoisie == 9){
                        array_push($user2BoardCards['ARCADIA'], $carteChoisie);
                    }elseif ($carteChoisie == 10 || $carteChoisie == 11 || $carteChoisie == 12){
                        array_push($user2BoardCards['ASTRALIA'], $carteChoisie);
                    }elseif($carteChoisie == 13 || $carteChoisie == 14 || $carteChoisie == 15 || $carteChoisie == 16){
                        array_push($user2BoardCards['THARUK'], $carteChoisie);
                    }elseif($carteChoisie == 17 || $carteChoisie == 18 || $carteChoisie == 19 || $carteChoisie == 20 || $carteChoisie == 21){
                        array_push($user2BoardCards['SOFIA'], $carteChoisie);
                    }



                    $round->setUser1BoardCards($user1BoardCards); //les 2 cartes restantes
                    $round->setUser2BoardCards($user2BoardCards); //la carte choisie
                    $round->setUser1ActionEnCours(null);
                    $game->getUser2()->setDejaPioche(0);


                }
                break;

        }

        $entityManager->flush();

        return $this->json(true);
    }



    /**
     * @Route("/join-game/{game}", name="join_game")
     */
    public function joinGame(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        Game $game
    ): Response {
        if($this->getUser()->getId() == $game->getUser1()->getId()){
            return $this->redirectToRoute('user_profil');
        }else{
            $user2 = $this->getUser();
            $user2->setDejaPioche(0);
            $game -> setUser2($user2);
            $entityManager->persist($user2);
            $entityManager->flush();
            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()

            ]);
        }



    }



    /**
     * @Route("/get-tout-game/{game}", name="get_tour")
     */
    public function getTour(
        Game $game, UserRepository $userRepository, EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()->getId() === $game->getUser1()->getId() && $game->getQuiJoue() === 1) {
            $user = $this->getUser();

            /*$user->setDejaPioche(0);
            $entityManager->persist($user);
            $entityManager->flush();*/
            return $this->json(true);
        }

        if ($this->getUser()->getId() === $game->getUser2()->getId() && $game->getQuiJoue() === 2) {
            /*$user = $this->getUser();
            $user->setDejaPioche(0);
            $entityManager->persist($user);
            $entityManager->flush();*/
            return $this->json(true);
        }

        return $this->json( false);
    }

    /**
     * @Route("/set-tour-game/{game}", name="set_tour")
     */
    public function setTour(
        EntityManagerInterface $entityManager,
        Game $game,
        Round $round
    ): Response {

        if($game->getQuiJoue()==1){
            $quiJoue = 2;

            $user2 = $game->getUser2();
            //SI J'AI VALIDE L'OFFRE, L'ADVERSAIRE PEUT JOUER MAIS PAS PIOCHER
            if($round->getUser1ActionEnCours() != 'OFFRE'){
                $user2->setDejaPioche(0);
            }

            $entityManager->persist($user2);

        }else{
            $quiJoue = 1;
            $user1 = $game->getUser1();
            //SI J'AI VALIDE L'OFFRE, L'ADVERSAIRE PEUT JOUER MAIS PAS PIOCHER
            if($round->getUser2ActionEnCours() != 'OFFRE'){
                $user1->setDejaPioche(0);
            }

            $entityManager->persist($user1);
        }

        $game->setQuiJoue($quiJoue);
        $entityManager->persist($game);
        $entityManager->flush();
        return $this->json(true);
    }
}