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

        //on verifie que la méthode POST est utilisée

        if($_SERVER['REQUEST_METHOD'] == 'POST'){

            //on verifie que le captcha est valide donc si recaptcha-response contient qqchose
            if(empty($_POST['recaptcha-response'])){

                header('Location: ../../templates/inscription/index.html.twig');
            }else{

                //on prépare l'url
                $url= 'https://www.google.com/recaptcha/api/siteverify?secret=6LdZBJAaAAAAAFJ_rm5kbLEMVg74Xad5rToI3-y0&response='.$_POST['recaptcha-response'];

                //on verifie si curl est installé

                if(function_exists('curl_version')){

                    $curl= curl_init($url);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    $response=curl_exec($curl);

                }else{
                    //on utilise file_get_content
                    $response = file_get_contents($url);
                }

                //on vérifie qu'on a une réponse
                if(empty($response)|| is_null($response)){
                    header('Location: mail_form.html');
                }else{
                    $data=json_decode($response);

                    if($data->success){

                        //si c'est un succès, alors:

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


                    }else{
                        header('Location : mail_form.html');
                    }
                }

            }



        }else{
            http_response_code(405);
            echo 'Méthode no autorisée';
        }


    }


}