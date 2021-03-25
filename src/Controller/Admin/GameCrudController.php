<?php

namespace App\Controller\Admin;

use App\Entity\Game;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GameCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Game::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id'),
            IntegerField::new('user1id', 'Id Joueur 1'),
            TextField::new('user1pseudo', 'Pseudo Joueur 1'),
            IntegerField::new('user2id', 'Id Joueur 2'),
            TextField::new('user2pseudo', 'Pseudo Joueur 2'),
            TextField::new('winnerpseudo', 'Gagnant'),
            DateField::new('created', 'Date de début'),
            DateField::new('ended', 'Date de fin'),






        ];
    }

}
