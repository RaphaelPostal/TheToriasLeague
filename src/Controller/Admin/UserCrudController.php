<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('pseudo', 'Pseudo'),
            TextField::new('firstname', 'Prénom'),
            TextField::new('lastname', 'Nom'),
            EmailField::new('email', 'Email'),
            IntegerField::new('elo', 'Points Elo'),
            TextField::new('password')->hideOnIndex(),
        ];
    }

}
