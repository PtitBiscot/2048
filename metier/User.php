<?php

class User
{
    private $id;
    private $pseudo;


    public function getId(): string
    {
        return $this->id;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }


}

?>