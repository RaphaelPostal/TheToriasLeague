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


        $opponent= $request->request->get('user2');

        if(isset($opponent)){
            $user2 = $userRepository->find($opponent);
        }else{
            $user2 = null;
        }




        //les 2 joueurs n'ont pas pioché
        /*$user1->setDejaPioche(0);

        if($user2 != null){
            /*$user2->setDejaPioche(0);
        }*/


        if ($user1 !== $user2) {
            $game = new Game();
            $game->setUser1($user1);
            $game->setUser2($user2);
            $game->setUser1DejaPioche(0);
            $game->setUser2DejaPioche(0);
            $entityManager->persist($user1, $user2);
            $game->setCreated(new \DateTime('now'));
            $game->setRoundEnCours(1);
            $game->setQuiJoue(1);

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
                'ECHANGE' => ["CartesChoisiesMoi"=>["Paire1"=>[], "Paire2"=>[]], "CartesChoisiesAdversaire"=>[]]
            ]);

            $round->setUser2Action([
                'SECRET' => false,
                'DEPOT' => [],
                'OFFRE' => ["CartesChoisiesMoi"=>[], "CartesChoisiesAdversaire"=>[]],
                'ECHANGE' => ["CartesChoisiesMoi"=>["Paire1"=>[], "Paire2"=>[]], "CartesChoisiesAdversaire"=>[]]
            ]);

            $round->setBoard([
                'EMPL1' => 'N',
                'EMPL2' => 'N',
                'EMPL3' => 'N',
                'EMPL4' => 'N',
                'EMPL5' => 'N',
                'EMPL6' => 'N',
                'EMPL7' => 'N'
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

            $num1 = $game->getRoundEnCours();
            $num2 = $num1-=1;
            $round = $game->getRounds()[$num2];


            return $this->render('game/show_game.html.twig', [
                'game' => $game,
                'round' => $round,

            ]);
        }else{
            return $this->redirectToRoute('user_profil');
        }

    }



    /**
     * @param Game $game
     * @route("/refresh/{game}", name="refresh_plateau_game")
     */
    public function refreshPlateauGame(CardRepository $cardRepository, Game $game, Round $round)
    {

        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        $num1 = $game->getRoundEnCours();
        $num2 = $num1-=1;
        $round = $game->getRounds()[$num2];

        /*var_dump($round);
        var_dump($round->getRoundNumber());*/


        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[$num2]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[$num2]->getUser1Action();
            $moi['board'] = $game->getRounds()[$num2]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[$num2]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[$num2]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[$num2]->getUser2BoardCards();
            if($game->getUser2()!==null){
                $adversaire['infos']=[];
                $adversaire['infos']['pseudo'] = $game->getUser2()->getPseudo();
                $adversaire['infos']['photo'] = $game->getUser2()->getPhoto();
                $adversaire['infos']['elo'] = $game->getUser2()->getElo();
            }else{
                $adversaire['infos'] = [];

            }


        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {

            $moi['handCards'] = $game->getRounds()[$num2]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[$num2]->getUser2Action();
            $moi['board'] = $game->getRounds()[$num2]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[$num2]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[$num2]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[$num2]->getUser1BoardCards();
            if($game->getUser1() !== null){
                $adversaire['infos']=[];
                $adversaire['infos']['pseudo'] = $game->getUser1()->getPseudo();
                $adversaire['infos']['photo'] = $game->getUser1()->getPhoto();
                $adversaire['infos']['elo'] = $game->getUser1()->getElo();
            }else{
                $adversaire['infos']= [];

            }


        } else {
            //redirection... je ne suis pas l'un des deux joueurs ???? PAS OBLIGATOIRE ????
            return $this->redirectToRoute('user_profil');
        }
                if( $round->getPioche() == [] && $round->getUser1HandCards() == [] && $round->getUser2HandCards() == [] && $round->getUser1ActionEnCours() == null && $round->getUser2ActionEnCours()==null){
                    return $this->render('game/resultats.html.twig', [
                        'game' => $game,

                    ]);
                }else{

                    var_dump($round->getBoard());
                    $plateau = $round->getBoard();

                    if($this->getUser()->getId() === $game->getUser1()->getId()){//SI JE SUIS J1



                        if($plateau['EMPL1']==1){
                            $moi['majorites']['KRULMO'] = 'win';



                        }elseif ($plateau['EMPL1']==2){

                            $moi['majorites']['KRULMO']='lose';

                        }else{
                            $moi['majorites']['KRULMO'] = 'egal';

                        }

                        if($plateau['EMPL2']==1){
                            $moi['majorites']['GANORMO'] = 'win';


                        }elseif ($plateau['EMPL2']==2){

                            $moi['majorites']['GANORMO']='lose';

                        }else{
                            $moi['majorites']['GANORMO'] = 'egal';

                        }

                        if($plateau['EMPL3']==1){
                            $moi['majorites']['RASDAR'] = 'win';


                        }elseif ($plateau['EMPL3']==2){

                            $moi['majorites']['RASDAR']='lose';

                        }else{
                            $moi['majorites']['RASDAR'] = 'egal';

                        }

                        if($plateau['EMPL4']==1){
                            $moi['majorites']['ARCADIA'] = 'win';


                        }elseif ($plateau['EMPL4']==2){

                            $moi['majorites']['ARCADIA']='lose';

                        }else{
                            $moi['majorites']['ARCADIA'] = 'egal';

                        }

                        if($plateau['EMPL5']==1){
                            $moi['majorites']['ASTRALIA'] = 'win';


                        }elseif ($plateau['EMPL5']==2){

                            $moi['majorites']['ASTRALIA']='lose';

                        }else{
                            $moi['majorites']['ASTRALIA'] = 'egal';

                        }

                        if($plateau['EMPL6']==1){
                            $moi['majorites']['THARUK'] = 'win';


                        }elseif ($plateau['EMPL6']==2){

                            $moi['majorites']['THARUK']='lose';

                        }else{
                            $moi['majorites']['THARUK'] = 'egal';

                        }

                        if($plateau['EMPL7']==1){
                            $moi['majorites']['SOFIA'] = 'win';


                        }elseif ($plateau['EMPL7']==2){

                            $moi['majorites']['SOFIA']='lose';

                        }else{
                            $moi['majorites']['SOFIA'] = 'egal';

                        }

                    }elseif ($this->getUser()->getId() === $game->getUser2()->getId()){

                        if($plateau['EMPL1']==2){
                            $moi['majorites']['KRULMO'] = 'win';


                        }elseif ($plateau['EMPL1']==1){

                            $moi['majorites']['KRULMO']='lose';

                        }else{
                            $moi['majorites']['KRULMO'] = 'egal';

                        }

                        if($plateau['EMPL2']==2){
                            $moi['majorites']['GANORMO'] = 'win';


                        }elseif ($plateau['EMPL2']==1){

                            $moi['majorites']['GANORMO']='lose';

                        }else{
                            $moi['majorites']['GANORMO'] = 'egal';

                        }

                        if($plateau['EMPL3']==2){
                            $moi['majorites']['RASDAR'] = 'win';


                        }elseif ($plateau['EMPL3']==1){

                            $moi['majorites']['RASDAR']='lose';

                        }else{
                            $moi['majorites']['RASDAR'] = 'egal';

                        }

                        if($plateau['EMPL4']==2){
                            $moi['majorites']['ARCADIA'] = 'win';


                        }elseif ($plateau['EMPL4']==1){

                            $moi['majorites']['ARCADIA']='lose';

                        }else{
                            $moi['majorites']['ARCADIA'] = 'egal';

                        }

                        if($plateau['EMPL5']==2){
                            $moi['majorites']['ASTRALIA'] = 'win';


                        }elseif ($plateau['EMPL5']==1){

                            $moi['majorites']['ASTRALIA']='lose';


                        }else{
                            $moi['majorites']['ASTRALIA'] = 'egal';

                        }

                        if($plateau['EMPL6']==2){
                            $moi['majorites']['THARUK'] = 'win';


                        }elseif ($plateau['EMPL6']==1){

                            $moi['majorites']['THARUK']='lose';


                        }else{
                            $moi['majorites']['THARUK'] = 'egal';

                        }

                        if($plateau['EMPL7']==2){
                            $moi['majorites']['SOFIA'] = 'win';


                        }elseif ($plateau['EMPL7']==1){

                            $moi['majorites']['SOFIA']='lose';


                        }else{
                            $moi['majorites']['SOFIA'] = 'egal';

                        }

                    }

                    ///////// FIN CALCUL DES MAJORITES
                    return $this->render('game/plateau_game.html.twig', [
                        'game' => $game,
                        'round' => $game->getRounds()[$num2],
                        'cards' => $tCards,
                        'moi' => $moi,
                        'adversaire' => $adversaire
                    ]);
                }





    }


    /**
     * @Route("/pioche/{game}", name="pioche")
     */
    public function pioche(
        EntityManagerInterface $entityManager,
        Request $request, Game $game, CardRepository $cardRepository):Response{



        $user = $this->getUser();

        $num1 = $game->getRoundEnCours();
        $num2 = $num1-=1;
        $round = $game->getRounds()[$num2]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId())
        {
            $joueur = 1;
        } elseif ($game->getUser2()->getId() === $user->getId()) {
            $joueur = 2;
        } else {
            /// On a un problème... On pourrait rediriger vers une page d'erreur.
        }

            $pioche = $round->getPioche();

        //var_dump($pioche);
        //tester s'il a pas déjà pioché
        //DEBUT MOULINETTE
        if($joueur == 1){
            if($game->getUser1DejaPioche() == 0){
                $dejaPioche = false;
            }elseif($game->getUser1DejaPioche() == 1){
                $dejaPioche = true;
            }
        }elseif($joueur == 2){
            if($game->getUser2DejaPioche() == 0){
                $dejaPioche = false;
            }elseif($game->getUser2DejaPioche() == 1){
                $dejaPioche = true;
            }
        }//FIN MOULINETTE

        if($dejaPioche === false){

            if ($joueur === 1) {
                //tester si l'autre joueur fait l'action echange ou offre
                if($round->getUser2ActionEnCours()==null){
                    $main = $round->getUser1HandCards();


                    $id_carte_tiree= array_pop($pioche);

                    $carte_tiree = $cardRepository->find($id_carte_tiree);
                    $main[]= $carte_tiree->getId();
                    $round->setUser1HandCards($main);
                    $round->setPioche($pioche);

                }else{
                    return $this->redirectToRoute('show_game', [
                        'game' => $game->getId()

                    ]);
                }



            }elseif ($joueur === 2){

                if($round->getUser1ActionEnCours()==null){

                $main = $round->getUser2HandCards();
                $id_carte_tiree= array_pop($pioche);

                $carte_tiree = $cardRepository->find($id_carte_tiree);
                $main[]= $carte_tiree->getId();
                $round->setUser2HandCards($main);
                $round->setPioche($pioche);
                }else{
                    return $this->redirectToRoute('show_game', [
                        'game' => $game->getId()

                    ]);
                }

            }
            if($joueur==1){
                $game->setUser1DejaPioche(1);
            }elseif ($joueur==2){
                $game->setUser2DejaPioche(1);
            }

            $entityManager->persist($round, $user);
            $entityManager->flush();
            $data=["your_turn"=>true];

            return $this->json($data);
        }else{
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

        $num1 = $game->getRoundEnCours();
        $num2 = $num1-=1;
        $round = $game->getRounds()[$num2]; //a gérer selon le round en cours


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
                    /*$game->getUser2()->setDejaPioche(1);*/
                    $game->setUser2DejaPioche(1);
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
                    /*$game->getUser1()->setDejaPioche(1);*/
                    $game->setUser1DejaPioche(1);
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
                    /*$game->getUser1()->setDejaPioche(0);*/
                    $game->setUser1DejaPioche(0);


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
                    /*$game->getUser2()->setDejaPioche(0);*/
                    $game->setUser2DejaPioche(0);


                }
                break;

            case 'echange':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                $carte3 = $request->request->get('carte3');
                $carte4 = $request->request->get('carte4');

                if ($joueur === 1) {
                    $round->setUser1ActionEnCours('ECHANGE');
                    /*$game->getUser2()->setDejaPioche(1);*/
                    $game->setUser2DejaPioche(1);
                    $actions = $round->getUser1Action(); //un tableau...
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire1'], $carte1);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire1'], $carte2);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire2'], $carte3);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire2'], $carte4);

                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    $indexCarte3 = array_search($carte3, $main);
                    $indexCarte4 = array_search($carte4, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    unset($main[$indexCarte3]);
                    unset($main[$indexCarte4]);
                    $round->setUser1HandCards($main);

                }elseif ($joueur === 2){

                    $round->setUser2ActionEnCours('ECHANGE');
                    /*$game->getUser1()->setDejaPioche(1);*/
                    $game->setUser1DejaPioche(1);
                    $actions = $round->getUser2Action(); //un tableau...
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire1'], $carte1);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire1'], $carte2);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire2'], $carte3);
                    array_push($actions['ECHANGE']['CartesChoisiesMoi']['Paire2'], $carte4);

                    $round->setUser2Action($actions); //je mets à jour le tableau dans bdd
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    $indexCarte3 = array_search($carte3, $main);
                    $indexCarte4 = array_search($carte4, $main);
                    unset($main[$indexCarte1]); //je supprime la carte de ma main
                    unset($main[$indexCarte2]);
                    unset($main[$indexCarte3]);
                    unset($main[$indexCarte4]);
                    $round->setUser2HandCards($main);


                }



                break;

            case 'echange_adv':
                if($joueur === 1){//joueur 1 qui fait son choix parmis les deux paires
                    $actions = $round->getUser2Action(); //un tableau...
                    if($request->request->get('PaireChoisie')=='paire1'){

                        $PaireChoisie = $actions['ECHANGE']['CartesChoisiesMoi']['Paire1'];
                        $IndexPaireChoisie = 'Paire1';
                    }else{
                        $PaireChoisie = $actions['ECHANGE']['CartesChoisiesMoi']['Paire2'];
                        $IndexPaireChoisie = 'Paire2';
                    }

                    array_push($actions['ECHANGE']['CartesChoisiesAdversaire'], $PaireChoisie);


                    $actions['ECHANGE']['CartesChoisiesMoi'][$IndexPaireChoisie]=[];

                    $round->setUser2Action($actions); //je mets à jour le tableau dans bdd
                    if($IndexPaireChoisie == 'Paire1'){
                        $cartesRestantes = $actions['ECHANGE']['CartesChoisiesMoi']['Paire2'];
                    }elseif ($IndexPaireChoisie == 'Paire2'){
                        $cartesRestantes = $actions['ECHANGE']['CartesChoisiesMoi']['Paire1'];
                    }


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

                    //REPARTITION POUR LE JOUEUR 1 avec sa paire choisie
                    foreach ($PaireChoisie as $carte){
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


                    $round->setUser2BoardCards($user2BoardCards); //les 2 cartes restantes
                    $round->setUser1BoardCards($user1BoardCards); //la carte choisie
                    $round->setUser2ActionEnCours(null);
                    /*$game->getUser1()->setDejaPioche(0);*/
                    $game->setUser1DejaPioche(0);
                }elseif($joueur === 2){

                    $actions = $round->getUser1Action(); //un tableau...
                    if($request->request->get('PaireChoisie')=='paire1'){

                        $PaireChoisie = $actions['ECHANGE']['CartesChoisiesMoi']['Paire1'];
                        $IndexPaireChoisie = 'Paire1';
                    }else{
                        $PaireChoisie = $actions['ECHANGE']['CartesChoisiesMoi']['Paire2'];
                        $IndexPaireChoisie = 'Paire2';
                    }

                    array_push($actions['ECHANGE']['CartesChoisiesAdversaire'], $PaireChoisie);


                    $actions['ECHANGE']['CartesChoisiesMoi'][$IndexPaireChoisie]=[];

                    $round->setUser1Action($actions); //je mets à jour le tableau dans bdd
                    if($IndexPaireChoisie == 'Paire1'){
                        $cartesRestantes = $actions['ECHANGE']['CartesChoisiesMoi']['Paire2'];
                    }elseif ($IndexPaireChoisie == 'Paire2'){
                        $cartesRestantes = $actions['ECHANGE']['CartesChoisiesMoi']['Paire1'];
                    }


                    $user2BoardCards = $round->getUser2BoardCards();
                    $user1BoardCards = $round->getUser1BoardCards();

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

                    //REPARTITION POUR LE JOUEUR 2 avec sa paire choisie
                    foreach ($PaireChoisie as $carte){
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


                    $round->setUser2BoardCards($user2BoardCards); //les 2 cartes restantes
                    $round->setUser1BoardCards($user1BoardCards); //la carte choisie
                    $round->setUser1ActionEnCours(null);
                    /*$game->getUser2()->setDejaPioche(0);*/
                    $game->setUser2DejaPioche(0);
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
            /*$user2->setDejaPioche(0);*/
            $game->setUser2DejaPioche(0);
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
        Game $game, UserRepository $userRepository, EntityManagerInterface $entityManager, Round $round
    ): Response {

        $num1 = $game->getRoundEnCours();
        $num2 = $num1-=1;
        $round = $game->getRounds()[$num2];
        /*var_dump($round);*/
        if($round->getPioche() == [] && $round->getUser1HandCards() == [] && $round->getUser2HandCards() == []){
            return $this->json('Fin de partie');
        }
        if($game->getUser2() != null){

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




        }else{
            return $this->json('Pas adversaire');
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
            if($round->getUser1ActionEnCours() != 'OFFRE' && $round->getUser1ActionEnCours() != 'ECHANGE'){
                /*$user2->setDejaPioche(0);*/
                $game->setUser2DejaPioche(0);
            }

            $entityManager->persist($user2);

        }else{
            $quiJoue = 1;
            $user1 = $game->getUser1();
            //SI J'AI VALIDE L'OFFRE, L'ADVERSAIRE PEUT JOUER MAIS PAS PIOCHER
            if($round->getUser2ActionEnCours() != 'OFFRE' && $round->getUser2ActionEnCours() != 'ECHANGE'){
                /*$user1->setDejaPioche(0);*/
                $game->setUser1DejaPioche(0);
            }

            $entityManager->persist($user1);
        }

        $game->setQuiJoue($quiJoue);
        $entityManager->persist($game);
        $entityManager->flush();
        return $this->json(true);
    }

    /**
     * @Route("/resultatsGame/{game}", name="resultats_game")
     */
    public function refreshResultats(
        EntityManagerInterface $entityManager,
        CardRepository $cardRepository,
        Game $game,
        Round $round
    ): Response {

        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        $num1 = $game->getRoundEnCours();
        $num2 = $num1-=1;
        $round = $game->getRounds()[$num2];

        $actions_j1 = $round->getUser1Action();
        $board_j1 = $round->getUser1BoardCards();
        $actions_j2 = $round->getUser2Action();
        $board_j2 = $round->getUser2BoardCards();



        if($actions_j1['SECRET'] != [] && $actions_j2['SECRET'] != []){
            $carte_secret_j1 = $actions_j1['SECRET'][0];
            $carte_secret_j2 = $actions_j2['SECRET'][0];
            if($carte_secret_j1 == 1 || $carte_secret_j1 == 2){
                array_push($board_j1['KRULMO'], $carte_secret_j1);
            }elseif ($carte_secret_j1 == 3 || $carte_secret_j1 == 4){
                array_push($board_j1['GANORMO'], $carte_secret_j1);
            }elseif($carte_secret_j1 == 5 || $carte_secret_j1 == 6){
                array_push($board_j1['RASDAR'], $carte_secret_j1);
            }elseif($carte_secret_j1 == 7 || $carte_secret_j1 == 8 || $carte_secret_j1 == 9){
                array_push($board_j1['ARCADIA'], $carte_secret_j1);
            }elseif ($carte_secret_j1 == 10 || $carte_secret_j1 == 11 || $carte_secret_j1 == 12){
                array_push($board_j1['ASTRALIA'], $carte_secret_j1);
            }elseif($carte_secret_j1 == 13 || $carte_secret_j1 == 14 || $carte_secret_j1 == 15 || $carte_secret_j1 == 16){
                array_push($board_j1['THARUK'], $carte_secret_j1);
            }elseif($carte_secret_j1 == 17 || $carte_secret_j1 == 18 || $carte_secret_j1 == 19 || $carte_secret_j1 == 20 || $carte_secret_j1 == 21){
                array_push($board_j1['SOFIA'], $carte_secret_j1);
            }

            unset($actions_j1['SECRET'][0]);

            if($carte_secret_j2 == 1 || $carte_secret_j2 == 2){
                array_push($board_j2['KRULMO'], $carte_secret_j2);
            }elseif ($carte_secret_j2 == 3 || $carte_secret_j2 == 4){
                array_push($board_j2['GANORMO'], $carte_secret_j2);
            }elseif($carte_secret_j2 == 5 || $carte_secret_j2 == 6){
                array_push($board_j2['RASDAR'], $carte_secret_j2);
            }elseif($carte_secret_j2 == 7 || $carte_secret_j2 == 8 || $carte_secret_j2 == 9){
                array_push($board_j2['ARCADIA'], $carte_secret_j2);
            }elseif ($carte_secret_j2 == 10 || $carte_secret_j2 == 11 || $carte_secret_j2 == 12){
                array_push($board_j2['ASTRALIA'], $carte_secret_j2);
            }elseif($carte_secret_j2 == 13 || $carte_secret_j2 == 14 || $carte_secret_j2 == 15 || $carte_secret_j2 == 16){
                array_push($board_j2['THARUK'], $carte_secret_j2);
            }elseif($carte_secret_j2 == 17 || $carte_secret_j2 == 18 || $carte_secret_j2 == 19 || $carte_secret_j2 == 20 || $carte_secret_j2 == 21){
                array_push($board_j2['SOFIA'], $carte_secret_j2);
            }

            unset($actions_j2['SECRET'][0]);
        }


        $round->setUser1BoardCards($board_j1);
        $round->setUser1Action($actions_j1);
        $round->setUser2BoardCards($board_j2);
        $round->setUser2Action($actions_j2);


        $round->setEnded(new \DateTime());
        $entityManager->persist($round);
        $entityManager->flush();

        //recupération des données
        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[$num2]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[$num2]->getUser1Action();
            $moi['board'] = $game->getRounds()[$num2]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[$num2]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[$num2]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[$num2]->getUser2BoardCards();



        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {

            $moi['handCards'] = $game->getRounds()[$num2]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[$num2]->getUser2Action();
            $moi['board'] = $game->getRounds()[$num2]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[$num2]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[$num2]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[$num2]->getUser1BoardCards();
        }



        /////////CALCUL DES MAJORITES
        $plateau = $round->getBoard();
        //KRULMO
        if(count($board_j1['KRULMO']) > count($board_j2['KRULMO'])){
            $plateau['EMPL1']=1;

        }elseif(count($board_j1['KRULMO']) == count($board_j2['KRULMO'])){
            $plateau['EMPL1']='N';
        }else{
            $plateau['EMPL1']=2;
        }

        //GANORMO
        if(count($board_j1['GANORMO']) > count($board_j2['GANORMO'])){
            $plateau['EMPL2']=1;

        }elseif(count($board_j1['GANORMO']) == count($board_j2['GANORMO'])){
            $plateau['EMPL2']='N';
        }else{
            $plateau['EMPL2']=2;
        }

        //RASDAR
        if(count($board_j1['RASDAR']) > count($board_j2['RASDAR'])){
            $plateau['EMPL3']=1;

        }elseif(count($board_j1['RASDAR']) == count($board_j2['RASDAR'])){
            $plateau['EMPL3']='N';
        }else{
            $plateau['EMPL3']=2;
        }

        //ARCADIA
        if(count($board_j1['ARCADIA']) > count($board_j2['ARCADIA'])){
            $plateau['EMPL4']=1;

        }elseif(count($board_j1['ARCADIA']) == count($board_j2['ARCADIA'])){
            $plateau['EMPL4']='N';
        }else{
            $plateau['EMPL4']=2;
        }

        //ASTRALIA
        if(count($board_j1['ASTRALIA']) > count($board_j2['ASTRALIA'])){
            $plateau['EMPL5']=1;

        }elseif(count($board_j1['ASTRALIA']) == count($board_j2['ASTRALIA'])){
            $plateau['EMPL5']='N';
        }else{
            $plateau['EMPL5']=2;
        }

        //THARUK
        if(count($board_j1['THARUK']) > count($board_j2['THARUK'])){
            $plateau['EMPL6']=1;

        }elseif(count($board_j1['THARUK']) == count($board_j2['THARUK'])){
            $plateau['EMPL6']='N';
        }else{
            $plateau['EMPL6']=2;
        }

        //SOFIA
        if(count($board_j1['SOFIA']) > count($board_j2['SOFIA'])){
            $plateau['EMPL7']=1;

        }elseif(count($board_j1['SOFIA']) == count($board_j2['SOFIA'])){
            $plateau['EMPL7']='N';
        }else{
            $plateau['EMPL7']=2;
        }

        $round->setBoard($plateau);

        $moi['majorites'] = [
            "KRULMO"=> false,
            "GANORMO"=> false,
            "RASDAR"=> false,
            "ARCADIA"=> false,
            "ASTRALIA"=> false,
            "THARUK"=> false,
            "SOFIA"=> false
        ];

        $moi['points']=0;
        $adversaire['points']=0;


        if($this->getUser()->getId() === $game->getUser1()->getId()){//SI JE SUIS J1



            if($plateau['EMPL1']==1){
                $moi['majorites']['KRULMO'] = 'win';
                $moi['points'] += 2;


            }elseif ($plateau['EMPL1']==2){

                $moi['majorites']['KRULMO']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['KRULMO'] = 'egal';

            }

            if($plateau['EMPL2']==1){
                $moi['majorites']['GANORMO'] = 'win';
                $moi['points'] += 2;

            }elseif ($plateau['EMPL2']==2){

                $moi['majorites']['GANORMO']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['GANORMO'] = 'egal';

            }

            if($plateau['EMPL3']==1){
                $moi['majorites']['RASDAR'] = 'win';
                $moi['points'] += 2;

            }elseif ($plateau['EMPL3']==2){

                $moi['majorites']['RASDAR']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['RASDAR'] = 'egal';

            }

            if($plateau['EMPL4']==1){
                $moi['majorites']['ARCADIA'] = 'win';
                $moi['points'] += 3;

            }elseif ($plateau['EMPL4']==2){

                $moi['majorites']['ARCADIA']='lose';
                $adversaire['points'] += 3;
            }else{
                $moi['majorites']['ARCADIA'] = 'egal';

            }

            if($plateau['EMPL5']==1){
                $moi['majorites']['ASTRALIA'] = 'win';
                $moi['points'] += 3;

            }elseif ($plateau['EMPL5']==2){

                $moi['majorites']['ASTRALIA']='lose';
                $adversaire['points'] += 3;
            }else{
                $moi['majorites']['ASTRALIA'] = 'egal';

            }

            if($plateau['EMPL6']==1){
                $moi['majorites']['THARUK'] = 'win';
                $moi['points'] += 4;

            }elseif ($plateau['EMPL6']==2){

                $moi['majorites']['THARUK']='lose';
                $adversaire['points'] += 4;
            }else{
                $moi['majorites']['THARUK'] = 'egal';

            }

            if($plateau['EMPL7']==1){
                $moi['majorites']['SOFIA'] = 'win';
                $moi['points'] += 5;

            }elseif ($plateau['EMPL7']==2){

                $moi['majorites']['SOFIA']='lose';
                $adversaire['points'] += 5;
            }else{
                $moi['majorites']['SOFIA'] = 'egal';

            }

        }elseif ($this->getUser()->getId() === $game->getUser2()->getId()){

            if($plateau['EMPL1']==2){
                $moi['majorites']['KRULMO'] = 'win';
                $moi['points'] += 2;

            }elseif ($plateau['EMPL1']==1){

                $moi['majorites']['KRULMO']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['KRULMO'] = 'egal';

            }

            if($plateau['EMPL2']==2){
                $moi['majorites']['GANORMO'] = 'win';
                $moi['points'] += 2;

            }elseif ($plateau['EMPL2']==1){

                $moi['majorites']['GANORMO']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['GANORMO'] = 'egal';

            }

            if($plateau['EMPL3']==2){
                $moi['majorites']['RASDAR'] = 'win';
                $moi['points'] += 2;

            }elseif ($plateau['EMPL3']==1){

                $moi['majorites']['RASDAR']='lose';
                $adversaire['points'] += 2;
            }else{
                $moi['majorites']['RASDAR'] = 'egal';

            }

            if($plateau['EMPL4']==2){
                $moi['majorites']['ARCADIA'] = 'win';
                $moi['points'] += 3;

            }elseif ($plateau['EMPL4']==1){

                $moi['majorites']['ARCADIA']='lose';
                $adversaire['points'] += 3;
            }else{
                $moi['majorites']['ARCADIA'] = 'egal';

            }

            if($plateau['EMPL5']==2){
                $moi['majorites']['ASTRALIA'] = 'win';
                $moi['points'] += 3;

            }elseif ($plateau['EMPL5']==1){

                $moi['majorites']['ASTRALIA']='lose';
                $adversaire['points'] += 3;

            }else{
                $moi['majorites']['ASTRALIA'] = 'egal';

            }

            if($plateau['EMPL6']==2){
                $moi['majorites']['THARUK'] = 'win';
                $moi['points'] += 4;

            }elseif ($plateau['EMPL6']==1){

                $moi['majorites']['THARUK']='lose';
                $adversaire['points'] += 4;

            }else{
                $moi['majorites']['THARUK'] = 'egal';

            }

            if($plateau['EMPL7']==2){
                $moi['majorites']['SOFIA'] = 'win';
                $moi['points'] += 5;

            }elseif ($plateau['EMPL7']==1){

                $moi['majorites']['SOFIA']='lose';
                $adversaire['points'] += 5;

            }else{
                $moi['majorites']['SOFIA'] = 'egal';

            }

        }

        ///////// FIN CALCUL DES MAJORITES

        $entityManager->persist($round);
        $entityManager->flush();

        return $this->render('game/plateau_resultats.html.twig', [
            'game' => $game,
            'round' => $game->getRounds()[$num2],
            'cards' => $tCards,
            'moi' => $moi,
            'adversaire' => $adversaire,
            'plateau'=>$plateau

        ]);

    }




    /**
     * @Route("/nextRound/{game}", name="next_round")
     */
    public function nextRound(
        Game $game,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CardRepository $cardRepository
    ): Response {


        //les 2 joueurs n'ont pas pioché
        /*$game->getUser1()->setDejaPioche(0);
        $game->getUser2()->setDejaPioche(0);*/
        $game->setUser1DejaPioche(0);
        $game->setUser2DejaPioche(0);

        $game->setQuiJoue(1);



            if($game->getRounds()[1] == null){
                //s'il n' y a pas eu de 2e manche
                $round = new Round();
                $round->setGame($game);
                $round->setCreated(new \DateTime('now'));
                $round->setRoundNumber(2);
                $game->setRoundEnCours(2);

                $ancien_board = $game->getRounds()[0]->getBoard();
                $round->setBoard($ancien_board);

            }elseif ($game->getRounds()[1] != null){
                if($game->getRounds()[1]->getEnded() != null){
                    //s'il y a eu une 2e manche et qu'elle est finie
                    if($game->getRounds()[2] == null){
                        //s'il n'existe pas déjà un 3e round
                        $round = new Round();
                        $round->setGame($game);
                        $round->setCreated(new \DateTime('now'));
                        $round->setRoundNumber(3);
                        $game->setRoundEnCours(3);

                        $ancien_board = $game->getRounds()[1]->getBoard();
                        $round->setBoard($ancien_board);
                    }else{
                        return $this->redirectToRoute('show_game', [
                            'game' => $game->getId()

                        ]);
                    }

                }else{

                    return $this->redirectToRoute('show_game', [
                        'game' => $game->getId()

                    ]);
                }

            }



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
                'ECHANGE' => ["CartesChoisiesMoi"=>["Paire1"=>[], "Paire2"=>[]], "CartesChoisiesAdversaire"=>[]]
            ]);

            $round->setUser2Action([
                'SECRET' => false,
                'DEPOT' => [],
                'OFFRE' => ["CartesChoisiesMoi"=>[], "CartesChoisiesAdversaire"=>[]],
                'ECHANGE' => ["CartesChoisiesMoi"=>["Paire1"=>[], "Paire2"=>[]], "CartesChoisiesAdversaire"=>[]]
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
            $entityManager->persist($game);
            $entityManager->flush();

            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()

            ]);


    }
}