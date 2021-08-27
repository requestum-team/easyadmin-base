<?php

namespace App\Controller\Admin;

use App\Controller\Form\UserImportForm;
use App\Entity\User;
use App\Service\UserImport\ImporterInterface;
use App\Service\UserImport\ImportException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * @Route("/import-users", name="user_import")
     */
    public function importAction(Request $request, AdminUrlGenerator $urlGenerator, ImporterInterface $importer) {
        $form = $this->createForm(UserImportForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->getData()["file"];

            try {
                $records = $importer->import($file);
                $this->addFlash('success', sprintf("%s users added.", $records));
                $url = $urlGenerator
                    ->setController(UserCrudController::class)
                    ->setAction('index')
                    ->generateUrl();

                return $this->redirect($url);
            } catch (ImportException $exception) {
                $form->addError(new FormError($exception->reason));
            }
        }

        return $this->render('user/import.html.twig', ['form' => $form->createView()]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(
                Crud::PAGE_INDEX,
                Action::new('user_import', 'Import')
                    ->linkToCrudAction('importAction')
                    ->createAsGlobalAction()
            )
        ;

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            EmailField::new('email'),
            TextField::new('username'),
            TextField::new('firstName'),
            TextField::new('lastName'),
        ];
    }
}
