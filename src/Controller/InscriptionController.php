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
        $existe = $userRepository->findOneby(["email"=>$_POST['prenom'].'@gmail.com']);
        if(!$existe){

            $new_user = new User();
            $new_user->setFirstName($_POST['prenom']);
            $new_user->setLastName('haha');
            $new_user->setBirthday(new \DateTime());
            $new_user->setEmail($_POST['prenom'].'@gmail.com');
            $new_user->setPassword(password_hash('1234', PASSWORD_ARGON2I));
            $new_user->setRoles(["ROLE_USER"]);

            $entityManager->persist($new_user);
            $entityManager->flush();
            return $this->render('inscription/valide.html.twig', [
                'prenom' => $_POST['prenom'],
                'mot' => $new_user->getPassword()


            ]);
        }else{

            return $this->render('inscription/index.html.twig', [
                'erreur'=>'Déjà inscrit !'


            ]);
        }

    }


}