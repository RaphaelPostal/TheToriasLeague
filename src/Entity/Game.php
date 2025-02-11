<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GameRepository::class)
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="games1")
     */
    private $user1;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="games2")
     */
    private $user2;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ended;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="winners")
     */
    private $winner;

    /**
     * @ORM\OneToMany(targetEntity=Round::class, mappedBy="game")
     */
    private $rounds;

    /**
     * @ORM\Column(type="integer")
     */
    private $quiJoue = 1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $roundEnCours;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $user1_deja_pioche;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $user2_deja_pioche;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $typeVictoire;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="game")
     */
    private $messages;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser1(): ?User
    {
        return $this->user1;
    }

    public function setUser1(?User $user1): self
    {
        $this->user1 = $user1;

        return $this;
    }

    public function getUser2(): ?User
    {
        return $this->user2;
    }

    public function setUser2(?User $user2): self
    {
        $this->user2 = $user2;

        return $this;
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

    public function setEnded(\DateTimeInterface $ended): self
    {
        $this->ended = $ended;

        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * @return Collection|Round[]
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): self
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds[] = $round;
            $round->setGame($this);
        }

        return $this;
    }

    public function removeRound(Round $round): self
    {
        if ($this->rounds->removeElement($round)) {
            // set the owning side to null (unless already changed)
            if ($round->getGame() === $this) {
                $round->setGame(null);
            }
        }

        return $this;
    }

    /*Fonctions supplémentaires pour le crud*/

    public function getUser1Id(){
        return $this->getUser1()->getId();
    }

    public function getUser2Id(){
        if($this->getUser2()){
            return $this->getUser2()->getId();
        }else{
            return '//Pas de joueur 2';
        }

    }

    public function getUser1Pseudo(){
        return $this->getUser1()->getPseudo();
    }

    public function getUser2Pseudo(){
        if($this->getUser2()){
            return $this->getUser2()->getPseudo();
        }else{
            return '//Pas de joueur 2';
        }

    }

    public function getWinnerPseudo(){
        if($this->getWinner() != null){
            return $this->getWinner()->getPseudo();
        }else{
            return 'Pas de gagnant';
        }

    }

    public function getQuiJoue(): ?int
    {
        return $this->quiJoue;
    }

    public function setQuiJoue(int $quiJoue): self
    {
        $this->quiJoue = $quiJoue;

        return $this;
    }

    public function getRoundEnCours(): ?int
    {
        return $this->roundEnCours;
    }

    public function setRoundEnCours(?int $roundEnCours): self
    {
        $this->roundEnCours = $roundEnCours;

        return $this;
    }

    public function getUser1DejaPioche(): ?int
    {
        return $this->user1_deja_pioche;
    }

    public function setUser1DejaPioche(?int $user1_deja_pioche): self
    {
        $this->user1_deja_pioche = $user1_deja_pioche;

        return $this;
    }

    public function getUser2DejaPioche(): ?int
    {
        return $this->user2_deja_pioche;
    }

    public function setUser2DejaPioche(?int $user2_deja_pioche): self
    {
        $this->user2_deja_pioche = $user2_deja_pioche;

        return $this;
    }

    public function getTypeVictoire(): ?string
    {
        return $this->typeVictoire;
    }

    public function setTypeVictoire(?string $typeVictoire): self
    {
        $this->typeVictoire = $typeVictoire;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setGame($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getGame() === $this) {
                $message->setGame(null);
            }
        }

        return $this;
    }

}