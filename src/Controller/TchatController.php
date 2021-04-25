<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class TchatController extends AbstractController
{
    /**
     * @Route("/refresh-tchat/{game}", name="refresh_tchat")
     */
    public function refreshTchat(Game $game): Response
    {
        $messages = $game->getMessages();
        return $this->render('tchat/messages.html.twig', [
            'messages'=>$messages,
        ]);
    }

    /**
     * @Route("/send-message/{game}", name="send_message")
     */
    public function send(Request $request, Game $game, EntityManagerInterface $entityManager)
    {
        $contenu = $request->request->get('contenu');
        $message = new Message();
        $message->setContenu($contenu);
        $message->setGame($game);
        $entityManager->persist($message);
        $entityManager->flush();


        return $this->json(true);
    }
}
