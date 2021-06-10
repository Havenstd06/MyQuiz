<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Score;
use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin.index')]
    public function index(): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAllDesc();

        return $this->render('admin/index.html.twig', compact('users'));
    }

    #[Route('/admin/users/new', name: 'admin.user.new')]
    public function userNew(Request $request, EmailVerifier $emailVerifier, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $entityManager = $this->getDoctrine()->getManager();

            $email = $request->get('email');
            $password = $request->get('password');

            $user = new User;

            $user->setEmail($email);
            $user->setIsVerified(false);

            $user->setPassword($passwordEncoder->encodePassword($user, $password));

            $user->setRole($request->get('role'));

            // Save
            $entityManager->persist($user);
            $entityManager->flush();

            $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('contact@myquiz.fr', '"MyQuiz"'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', $user->getEmail() . ' account has been successfully created.');

            return $this->redirectToRoute('admin.index');
        }

        return $this->render('admin/users/new.html.twig');
    }

    #[Route('/admin/users/{id}', name: 'admin.user.edit')]
    public function userEdit(Request $request, User $user, EmailVerifier $emailVerifier, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $entityManager = $this->getDoctrine()->getManager();

            $email = $request->get('email');
            $password = $request->get('password');

            if ($email !== $user->getEmail()) {
                $user->setEmail($email);
                $user->setIsVerified(false);

                $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('contact@myquiz.fr', '"MyQuiz"'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );
            }

            if ($password) {
                $user->setPassword($passwordEncoder->encodePassword($user, $password));
            }

            $user->setRole($request->get('role'));

            // Save
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $user->getEmail() . ' account has been successfully updated.');

            return $this->redirectToRoute('admin.index');
        }

        return $this->render('admin/users/edit.html.twig', compact('user'));
    }

    #[Route('/admin/users/{id}/delete', name: 'admin.user.delete')]
    public function userDelete(User $user): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        if ($user->getRole()) {
            $this->addFlash('error', 'You can\t delete an admin');

            return $this->redirectToRoute('home');
        }

        $deleteUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($deleteUser);
        $entityManager->flush();

        $this->addFlash('success', $user->getEmail() . ' account has been successfully deleted.');

        return $this->redirectToRoute('admin.index');
    }

    #[Route('/admin/categories', name: 'admin.categorie')]
    public function categorie(): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        $categories = $this->getDoctrine()
            ->getRepository(Categorie::class)
            ->findAllDesc();

        return $this->render('admin/categories/index.html.twig', compact('categories'));
    }

    #[Route('/admin/categories/{id}', name: 'admin.categorie.edit')]
    public function categorieEdit(Request $request, Categorie $categorie): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $entityManager = $this->getDoctrine()->getManager();

            $categorie->setName($request->get('name'));

            foreach ($categorie->getQuestions() as $question) {
                if ($request->get('question-' . $question->getId())) {
                    $question->setQuestion($request->get('question-' . $question->getId()));
                }


                foreach ($question->getReponses() as $reponses) {
                    if ($request->get('reponse-' . $reponses->getId())) {
                        $reponses->setReponse($request->get('reponse-' . $reponses->getId()));
                    }
                }
            }

            // save
            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', $categorie->getName() . ' category has been successfully updated.');

            return $this->redirectToRoute('admin.categorie.edit', ['id' => $categorie->getId()]);
        }


        return $this->render('admin/categories/edit.html.twig', compact('categorie'));
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin.categorie.delete')]
    public function categoriesDelete(Categorie $categorie): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        $deleteCategorie = $this->getDoctrine()->getRepository(Categorie::class)->findOneBy(['id' => $categorie->getId()]);
        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($deleteCategorie);
        $entityManager->flush();

        $this->addFlash('success', $categorie->getName() . ' category has been successfully deleted.');

        return $this->redirectToRoute('admin.categorie');
    }

    #[Route('/admin/scores', name: 'admin.scores')]
    public function scores(): Response
    {
        if ($this->getUser() === null || ! $this->getUser()->getRole()) {
            $this->addFlash('error', 'You are not allowed to go there.');

            return $this->redirectToRoute('home');
        }

        $scores = $this->getDoctrine()
            ->getRepository(Score::class)
            ->findAllDesc();

        return $this->render('admin/scores/index.html.twig', compact('scores'));
    }
}
