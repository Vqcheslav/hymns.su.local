<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Hymn;
use App\Service\HymnService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use RuntimeException;

class HymnCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly HymnService $hymnService,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Hymn::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->renderContentMaximized()
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setSearchFields(['book.bookId', 'number', 'title', 'category']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(
                pageName: Crud::PAGE_INDEX,
                actionName: Action::DELETE,
                callable: function (Action $action) {
                    return $action->displayIf(function (Hymn $hymn) {
                        return $hymn->getBook()?->getBookId() === 'custom';
                    });
                },
            );
    }

    public function configureFields(string $pageName): iterable
    {
        $categories = $this->hymnService->getHymnCategories()->getData();
        $titles = array_column($categories, 'title');
        $choices = array_combine($titles, $titles);

        return [
            TextField::new('hymnId')
                ->setLabel('Hymn Id')
                ->setDisabled()
                ->hideWhenCreating(),
            AssociationField::new('book', 'Book Id')
                ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                    return $queryBuilder
                        ->where("entity.bookId in ('custom', 'songbook-demyansk')")
                        ->orderBy('entity.bookId');
                }),
            IntegerField::new('number')
                ->hideWhenCreating(),
            TextField::new('title')
                ->setHtmlAttribute('autocomplete', 'off'),
            ChoiceField::new('category')
                ->setChoices($choices),
            TextField::new('tone')
                ->hideOnIndex()
                ->setRequired(false)
                ->setEmptyData(''),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Hymn $entityInstance */
        $book = $entityInstance->getBook();

        if ( ! $book instanceof Book) {
            throw new RuntimeException('Book not found');
        }

        $maxNumber = $book->getTotalSongs();
        $lastNumber = $maxNumber + 1;

        $hymnId = $this->hymnService->generateHymnId($lastNumber, $entityInstance->getTitle());
        $entityInstance->setHymnId($hymnId);
        $entityInstance->setBook($book);
        $entityInstance->setNumber($lastNumber);
        $entityInstance->setUpdatedAt(new DateTimeImmutable());

        $book->setTotalSongs($lastNumber);
        $entityManager->persist($book);

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Hymn $entityInstance */
        $hymnRepository = $entityManager->getRepository(Hymn::class);
        $oldHymnTitle = $hymnRepository->getTitleByHymnId($entityInstance->getHymnId());

        // If the title has been changed, we generate a new hymn ID
        if (mb_strtolower($entityInstance->getTitle()) !== mb_strtolower($oldHymnTitle)) {
            $hymnId = $this->hymnService->generateHymnId($entityInstance->getNumber(), $entityInstance->getTitle());
            $entityInstance->setHymnId($hymnId);
        }

        $entityInstance->setUpdatedAt(new DateTimeImmutable());

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var Hymn $entityInstance */
        $book = $entityInstance->getBook();

        if ($book instanceof Book) {
            $book->setTotalSongs($book->getTotalSongs() - 1);
            $entityManager->persist($book);
        }

        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }
}
