<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Hymn;
use App\Entity\Verse;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_verse_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Гимны: Админ-панель')
            ->setFaviconPath('/img/hymns/favicon.png')
            ->generateRelativeUrls()
            ->setLocales(['ru'])
            ->setTranslationDomain('admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Back to the website', 'fa fa-home', $this->generateUrl('homepage'));
        yield MenuItem::linkToCrud('Books', 'fas fa-book', Book::class);
        yield MenuItem::linkToCrud('Hymns', 'fas fa-music', Hymn::class);
        yield MenuItem::linkToCrud('Verses', 'fas fa-list', Verse::class);
    }
}
