<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
    public function search(EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {

        if(isset($_GET['searchPseudo'])){
            $em = $this->getDoctrine()->getManager(); //on appelle Doctrine
            $query = $em->createQuery( //creation de la requête
                'SELECT u
    FROM App\Entity\User u
    WHERE u.pseudo LIKE :pseudo
    '
            )->setParameter('pseudo', '%'.$_GET['searchPseudo'].'%');


            $users = $query->getResult();
        }else{
            return $this->redirectToRoute('user_profil');
        }


        return $this->render('user/resultats_recherche.html.twig', [

            'users'=>$users,
        ]);
    }
}
