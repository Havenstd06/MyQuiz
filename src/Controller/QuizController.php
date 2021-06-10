<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Score;
use App\Entity\Reponse;
use App\Entity\Categorie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuizController extends AbstractController
{
    #[Route('/quiz', name: 'quiz')]
    public function index(): Response
    {
        $categories = $this->getDoctrine()->getRepository(Categorie::class)->findAll();

        return $this->render('quiz/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/quiz/{id}/{number}', name: 'quiz.categorie')]
    public function show(Categorie $categorie, Request $request): Response
    {
        $question_id = $request->get('number');

        $question = $categorie->getQuestions()[$question_id];

        try {
            $reponses = $this->getDoctrine()
                ->getRepository(Reponse::class)
                ->findBy(['question' => $question->getId()]);

            shuffle($reponses);

        } catch (\Error $e) {
            if ($this->getUser() !== null) {
                $score = $this->getDoctrine()
                    ->getRepository(Score::class)
                    ->findByUserScore($this->getUser()->getId())->getScore();

                return $this->quizEnd($categorie, $score);
            }

            $session = $request->getSession();

            $score = $session->get('score');

            return $this->quizEnd($categorie, $score);
        }


        if ($request->isMethod('POST')) {
            $reponse = $this->getDoctrine()
            ->getRepository(Reponse::class)
            ->findOneBy(['question' => $question->getId(), 'reponseExpected' => 1]);

            $reponse = $reponse->getReponse();

            $idReponse = $request->get('reponse');

            $result = $this->getDoctrine()
                ->getRepository(Reponse::class)
                ->findOneBy(['id' => $idReponse, 'reponseExpected' => 1]);

            if ($this->getUser() === null) {
                if ($question_id === "0") {
                    $session = new Session();

                    $session->set('name', $categorie->getName());
                    $session->set('date', date("Y-m-d H:i:s"));
                    $session->set('nbQuestion', count($categorie->getQuestions()));

                    if ($result) {
                        $session->set('score', 1);
                    } else {
                        $session->set('score', 0);
                    }

                } else {
                    $session = $request->getSession();

                    if ($result) {
                        $session->set('score', $session->get('score') + 1);
                    }
                }

            } else {
                $id_user = $this->getUser()->getId();

                $entityManager = $this->getDoctrine()->getManager();

                if ($question_id === "0") {
                    $date = date("Y-m-d H:i:s");

                    $addScore = new Score();
                    $addScore->setDate($date);
                    $addScore->setCategorie($categorie);

                    if ($result) {
                        $addScore->setScore(1);
                    } else {
                        $addScore->setScore(0);
                    }


                } else {
                    $addScore = $this->getDoctrine()
                        ->getRepository(Score::class)
                        ->findByUserScore($id_user);

                    if ($result) {
                        $addScore->setScore($addScore->getScore() + 1);
                    }
                }

                $addScore->setUserId($id_user);
                $addScore->setName($categorie->getName());

                $entityManager->persist($addScore);
                $entityManager->flush();
            }

            $question_id++;

        }


        if (! $question || ! $categorie) {
            return $this->redirectToRoute('quiz');
        }

        return $this->render('quiz/show.html.twig', [
            'categorie'     => $categorie,
            'question'      => $question,
            'result'        => $result ?? '',
            'reponses'      => $reponses,
            'reponse'       => $reponse ?? null,
            'nextQuestion'  => $question_id ?? 1,
        ]);
    }

    #[Route('/quiz/new', name: 'quiz.new')]
    public function quizNew(Request $request)
    {
        if ($this->getUser() === null) {
            $this->addFlash('error', 'You are not allowed to go here, please register first.');

            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $newName = $request->get('name');
            $nbQuestions = $request->get('number');

            $categorie = new Categorie();
            $categorie->setName($newName);

            // save
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($categorie);
            $entityManager->flush();

            return $this->redirectToRoute('quiz.create', [
                'id'        => $categorie->getId(),
                'number'    => $nbQuestions
            ]);
        }

        return $this->render('quiz/new.html.twig');
    }

    #[Route('/quiz/new/{id}/{number}', name: 'quiz.create')]
    public function quizCreate(Request $request, Categorie $categorie, int $number)
    {
        if ($categorie->getQuestions()[0]) {
            $this->addFlash('error', 'This quiz has already been created.');

            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {

            $nbQuestion = 1;
            while ($nbQuestion <= $number) {
                $entityManager = $this->getDoctrine()->getManager();

                $questionName = $request->get('question-' . $nbQuestion);

                $question = new Question();
                $question->setCategorie($categorie);
                $question->setQuestion($questionName);


                $nbReponse = 1;
                while ($nbReponse <= 3) {
                    $reponseName = $request->get('question-' . $nbQuestion . '-reponse-' . $nbReponse);

                    $reponse = new Reponse();
                    $reponse->setQuestion($question);
                    $reponse->setReponse($reponseName);

                    if ($nbReponse === 1) {
                        $reponse->setReponseExpected(true);
                    } else {
                        $reponse->setReponseExpected(false);
                    }

                    // save
                    $entityManager->persist($reponse);

                    $nbReponse++;
                }

                // save
                $entityManager->persist($question);
                $entityManager->flush();

                $nbQuestion++;
            }

            $this->addFlash('success', 'Your quiz has been successfully created.');

            return $this->redirectToRoute('quiz');
        }

        return $this->render('quiz/newCreate.html.twig', [
            'categorie'     => $categorie,
            'nbQuestions'   => $number
        ]);
    }

    #[Route('/quiz/{id}', name: 'quiz.end')]
    public function quizEnd(Categorie $categorie, int $score)
    {
        return $this->render('quiz/end.html.twig', compact('categorie', 'score'));
    }
}
