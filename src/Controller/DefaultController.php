<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/mail", name="send_mail")
     */
    public function sendMail(MailerInterface $mailer): Response
    {
        $email = (new TemplatedEmail())
            ->to('raphael.postal08@gmail.com')
            ->from('raphael.postal@etudiant.univ-reims.fr')
            ->subject('Test mail')
            ->htmlTemplate('mail/mail.html.twig')
            ->context([
                'prenom' => 'Raph',
                'nom' => 'Postal',
            ]);

        $mailer->send($email);

        return $this->render('mail/confirmation.html.twig');
    }
}