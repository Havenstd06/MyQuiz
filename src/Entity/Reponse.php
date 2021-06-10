<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReponseRepository::class)
 */
class Reponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $reponse;

    /**
     * @ORM\ManyToOne(targetEntity="Question", inversedBy="reponses")
     * @ORM\JoinColumn(name="id_question", referencedColumnName="id")
     */
    private $question;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="reponse_expected", type="boolean", nullable=true)
     */
    private $reponseExpected;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): self
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): self
    {
        $this->question = $question;

        return $this;
    }
    public function getReponseExpected(): ?bool
    {
        return $this->reponseExpected;
    }

    public function setReponseExpected(?bool $reponseExpected): self
    {
        $this->reponseExpected = $reponseExpected;

        return $this;
    }
}
