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

class UserController extends AbstractController
{
    #[Route('/u/{id}', name: 'user.profile')]
    public function index(User $user): Response
    {
        if ($this->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('home');
        }

        $scores = $this->getDoctrine()
            ->getRepository(Score::class)
            ->findBy(['user_id' => $user->getId()], ['id' => 'DESC']);

        return $this->render('user/index.html.twig', compact('user', 'scores'));
    }

    #[Route('/u/{id}/settings', name: 'user.settings')]
    public function settings(Request $request, User $user, EmailVerifier $emailVerifier, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($this->getUser()->getId() !== $user->getId()) {
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $entityManager = $this->getDoctrine()->getManager();

            $email = $request->get('email');
            $password = $request->get('password');
            $passwordConfirm = $request->get('confirm-password');

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

            if ($password && $passwordConfirm) {
                if ($password !== $passwordConfirm) {
                    $this->addFlash('error', 'Your password does not matched.');

                    return $this->redirectToRoute('user.settings', ['id' => $user->getId()]);
                }
                $user->setPassword($passwordEncoder->encodePassword($user, $password));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been successfully updated. If you have modified your email, a verification email has been sent to you.');

            return $this->redirectToRoute('user.profile', ['id' => $user->getId()]);
        }

        return $this->render('user/settings.html.twig', compact('user'));
    }
}
