<?php

namespace App\Controller;

use App\Entity\PostCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostCategoryController extends AbstractController
{
    /**
     * @Route("/post/category", name="post_category")
     *
     * @return Response
     */
    public function index(EntityManagerInterface $entityManager): Response
    {
        $postCategory = new PostCategory();
        $postCategory->setTitle('Catégorie 1');

        //equivalent à $entityManager = $this->getDoctrine()->getManager() sans injection de dépendance
        $entityManager->persist($postCategory);//j'associe postCategory à la base de données
        $entityManager->flush(); //j'écris dans la base de données.


        return $this->render('post_category/index.html.twig', [
            'postCategory' => $postCategory
        ]);
    }
}