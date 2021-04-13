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
                                    if($_POST['mdp'] == $_POST['conf_password']){
                                        $new_user->setPassword(password_hash($_POST['mdp'], PASSWORD_ARGON2I));
                                    }else{

                                        return $this->render('inscription/index.html.twig', [
                                            'erreur'=>'Les mots de passe sont différents !'

                                        ]);
                                    }

                                    $new_user->setRoles(["ROLE_JOUEUR"]);
                                    $new_user->setElo(1000);
                                    if(isset($_POST['photo'])){
                                        $new_user->setPhoto($_POST['photo'].'.png');
                                    }else{
                                        $new_user->setPhoto('tharuk.png');
                                    }

                                    $new_user->setInscription(new \DateTime());


                                    $entityManager->persist($new_user);
                                    $entityManager->flush();

                                    //ENVOI DE MAIL

                                    $destinataire=$new_user->getEmail();
                                    $subject='Inscription - The Toria\'s League';
                                    $headers='From: iro.games.troyes@gmail.com';
                                    $headers.='Content-type: text/html; charset=utf-8';
                                    $message='<h1>Félicitations '.$new_user->getFirstName().' !</h1><p>Vous venez de vous inscrire pour l\'aventure de The Toria\'s League ! Devenez un(e) grand(e) chef(fe) sous le pseudo '.$new_user->getPseudo().'</p>';
                                    mail($destinataire, $subject, $message, $headers);
                                    //
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