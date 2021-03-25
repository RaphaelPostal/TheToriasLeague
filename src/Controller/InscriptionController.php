<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;


class InscriptionController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     */
    public function index(): Response
    {
        return $this->render('inscription/index.html.twig', [
            'erreur'=>''
        ]);
    }

    /**
     * @Route("/verify", name="verify_inscription")
     */

    public function verifyInscription(EntityManagerInterface $entityManager, UserRepository $userRepository) : Response
    {
        //test si email dejà pris
        $existe = $userRepository->findOneby(["email"=>$_POST['email']]);
        if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
            if(!$existe){
                $new_user = new User();
                $new_user->setFirstName($_POST['prenom']);
                $new_user->setLastName($_POST['nom']);
                $new_user->setBirthday(new \DateTime($_POST['birthday']));
                $new_user->setEmail($_POST['email']);
                $new_user->setPseudo($_POST['pseudo']);
                $new_user->setPassword(password_hash($_POST['mdp'], PASSWORD_ARGON2I));
                $new_user->setRoles(["ROLE_JOUEUR"]);
                $new_user->setElo(1000);

                $entityManager->persist($new_user);
                $entityManager->flush();
                return $this->render('inscription/valide.html.twig', [
                    'new_user'=>$new_user,


                ]);
            }else{
                return $this->render('inscription/index.html.twig', [
                    'erreur'=>'Cet utilisateur est déjà inscrit !'


                ]);
            }

        }else{

            return $this->render('inscription/index.html.twig', [
                'erreur'=>'Email invalide !'


            ]);
        }

    }


}