<?php


class Game
{
    private $pseudo;
    private $gagne;
    private $score;

    /**
     * Game constructor.
     * @param $pseudo
     * @param $gagne
     * @param $score
     */
    public function __construct($pseudo)
    {
        $this->pseudo = $pseudo;
        $this->gagne = 0;
        $this->score = 0;
    }

    /**
     * @return mixed
     */
    public function getPseudo()
    {
        return $this->pseudo;
    }

    /**
     * @param mixed $pseudo
     */
    public function setPseudo($pseudo): void
    {
        $this->pseudo = $pseudo;
    }

    /**
     * @return int
     */
    public function getGagne(): int
    {
        return $this->gagne;
    }

    /**
     * @param int $gagne
     */
    public function setGagne(int $gagne): void
    {
        $this->gagne = $gagne;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore(int $score): void
    {
        $this->score = $score;
    }

}