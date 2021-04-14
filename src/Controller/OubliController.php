<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OubliController extends AbstractController
{
    /**
     * @Route("/oubli", name="oubli")
     */
    public function index(): Response
    {
        return $this->render('oubli/index.html.twig');
    }


    /**
     * @Route("/recup", name="recup")
     */
    public function recup(EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        //fonction génération chaine
        function random_string($length){
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $string = '';
            for($i=0; $i<$length; $i++){
                $string .= $chars[rand(0, strlen($chars)-1)];
            }
            return $string;
        }



        if($userRepository->findOneBy(['email' => $_POST['email_oubli']]) !== null){

            $user = $userRepository->findOneBy(['email' => $_POST['email_oubli']]);
            $new_mdp = random_string(20);
            $user->setPassword(password_hash($new_mdp, PASSWORD_ARGON2I));

            //ENVOI DE MAIL

            $destinataire=$user->getEmail();
            $subject='Récupération de mot de passe - The Toria\'s League';
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
            $headers[]='From: iro.games.troyes@gmail.com';
            $message='<h1>Bonjour '.$user->getFirstName().'.</h1>
            <p>Suite à une demande de ta part, un nouveau mot de passe a été attribué à ton compte. Le voici :</p>
            <strong>'.$new_mdp.'</strong><br>
            <p>Tu devras l\'utiliser la prochaine fois que tu te connecteras. Nous t\'invitons ensuite à le modifier via les paramètres de ton profil.</p>';

            mail($destinataire, $subject, $message, implode("\r\n", $headers));
            //

            $entityManager->persist($user);
            $entityManager->flush();
        }else{
            return $this->render('oubli/index.html.twig',[
                'erreur'=>'Cet utilisateur n\'est pas inscrit !'
            ]);
        }

        return $this->render('oubli/index.html.twig',[
            'erreur'=>'Un mail vient d\'être envoyé à '.$user->getEmail()
        ]);

    }
}
