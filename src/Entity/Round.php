<?php

namespace App\Entity;

use App\Repository\RoundRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoundRepository::class)
 */
class Round
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ended;

    /**
     * @ORM\ManyToOne(targetEntity=Game::class, inversedBy="rounds")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $game;

    /**
     * @ORM\Column(type="array")
     */
    private $User1BoardCards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $User2BoardCards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $board = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $removedCard;

    /**
     * @ORM\Column(type="array")
     */
    private $User1Action = [];

    /**
     * @ORM\Column(type="array")
     */
    private $User2Action = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $RoundNumber;

    /**
     * @ORM\Column(type="array")
     */
    private $User1HandCards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $User2HandCards = [];

    /**
     * @ORM\Column(type="array")
     */
    private $pioche = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $User1ActionEnCours;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $User2ActionEnCours;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getEnded(): ?\DateTimeInterface
    {
        return $this->ended;
    }

    public function setEnded(?\DateTimeInterface $ended): self
    {
        $this->ended = $ended;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getUser1BoardCards(): ?array
    {
        return $this->User1BoardCards;
    }

    public function setUser1BoardCards(array $User1BoardCards): self
    {
        $this->User1BoardCards = $User1BoardCards;

        return $this;
    }

    public function getUser2BoardCards(): ?array
    {
        return $this->User2BoardCards;
    }

    public function setUser2BoardCards(array $User2BoardCards): self
    {
        $this->User2BoardCards = $User2BoardCards;

        return $this;
    }

    public function getBoard(): ?array
    {
        return $this->board;
    }

    public function setBoard(array $board): self
    {
        $this->board = $board;

        return $this;
    }

    public function getRemovedCard(): ?int
    {
        return $this->removedCard;
    }

    public function setRemovedCard(int $removedCard): self
    {
        $this->removedCard = $removedCard;

        return $this;
    }

    public function getUser1Action(): ?array
    {
        return $this->User1Action;
    }

    public function setUser1Action(array $User1Action): self
    {
        $this->User1Action = $User1Action;

        return $this;
    }

    public function getUser2Action(): ?array
    {
        return $this->User2Action;
    }

    public function setUser2Action(array $User2Action): self
    {
        $this->User2Action = $User2Action;

        return $this;
    }

    public function getRoundNumber(): ?int
    {
        return $this->RoundNumber;
    }

    public function setRoundNumber(int $RoundNumber): self
    {
        $this->RoundNumber = $RoundNumber;

        return $this;
    }

    public function getUser1HandCards(): ?array
    {
        return $this->User1HandCards;
    }

    public function setUser1HandCards(array $User1HandCards): self
    {
        $this->User1HandCards = $User1HandCards;

        return $this;
    }

    public function getUser2HandCards(): ?array
    {
        return $this->User2HandCards;
    }

    public function setUser2HandCards(array $User2HandCards): self
    {
        $this->User2HandCards = $User2HandCards;

        return $this;
    }

    public function getPioche(): ?array
    {
        return $this->pioche;
    }

    public function setPioche(array $pioche): self
    {
        $this->pioche = $pioche;

        return $this;
    }

    public function getUser1ActionEnCours(): ?string
    {
        return $this->User1ActionEnCours;
    }

    public function setUser1ActionEnCours(?string $User1ActionEnCours): self
    {
        $this->User1ActionEnCours = $User1ActionEnCours;

        return $this;
    }

    public function getUser2ActionEnCours(): ?string
    {
        return $this->User2ActionEnCours;
    }

    public function setUser2ActionEnCours(?string $User2ActionEnCours): self
    {
        $this->User2ActionEnCours = $User2ActionEnCours;

        return $this;
    }
}