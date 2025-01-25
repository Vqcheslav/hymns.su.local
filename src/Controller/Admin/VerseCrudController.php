<?php

namespace App\Controller\Admin;

use App\Entity\Hymn;
use App\Entity\Verse;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class VerseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Verse::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setDefaultSort(['verseId' => 'DESC'])
            ->setSearchFields(['hymn.hymnId', 'lyrics']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('verseId', 'Verse Id')
                ->hideOnForm()
                ->setSortable(true),
            AssociationField::new('hymn', 'Hymn Id')
                ->setQueryBuilder(function ($queryBuilder) {
                    return $queryBuilder->orderBy('entity.updatedAt', 'DESC');
                }),
            IntegerField::new('position'),
            BooleanField::new('isChorus'),
            TextareaField::new('lyrics'),
            TextareaField::new('chords')
                ->hideOnIndex()
                ->setRequired(false)
                ->setEmptyData(''),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Verse $entityInstance */
        $hymn = $entityInstance->getHymn();

        if ($hymn instanceof Hymn) {
            $hymn->setUpdatedAt(new DateTimeImmutable());
            $entityManager->persist($hymn);
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Verse $entityInstance */
        $hymn = $entityInstance->getHymn();

        if ($hymn instanceof Hymn) {
            $hymn->setUpdatedAt(new DateTimeImmutable());
            $entityManager->persist($hymn);
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Verse $entityInstance */
        $hymn = $entityInstance->getHymn();

        if ($hymn instanceof Hymn) {
            $hymn->setUpdatedAt(new DateTimeImmutable());
            $entityManager->persist($hymn);
        }

        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }
}
