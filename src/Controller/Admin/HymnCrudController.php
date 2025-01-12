<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Hymn;
use App\Service\HymnService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
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
            ->setDefaultSort(['updatedAt' => 'ASC'])
            ->setSearchFields(['book.bookId', 'number', 'title', 'category']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->displayIf(function (Hymn $hymn) {
                    return $hymn->getBook()?->getBookId() === 'custom';
                });
            });
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('hymnId')
                ->setLabel('Hymn Id')
                ->setDisabled()
                ->hideWhenCreating(),
            AssociationField::new('book')
                ->setLabel('Book Id')
                ->hideOnForm(),
            NumberField::new('number')
                ->hideWhenCreating(),
            TextField::new('title'),
            TextField::new('category'),
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
        $book = $entityManager->find(Book::class, 'custom');

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
