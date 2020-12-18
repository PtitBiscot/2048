<?php

class GameDAO
{
    private $db;

    public function __construct()
    {
        $this->db = SqliteConnexion::getInstance()->getConnexion();
    }

    //créer une partie
    public function insert(Game $game)
    {
        $req = $this->db->prepare("INSERT INTO PARTIES(pseudo, gagne, score) VALUES (:pseudo, :gagne, :score)");
        $req->execute(array(
            "pseudo" => $game->getPseudo(),
            "gagne" => 0,
            "score" => 0
        ));
    }

    //retourne la position du joueur dans le classement des meilleurs scores
    public function getPosition($pseudo){
        $req = $this->db->prepare("select count(pseudo)+1 as rank from (select pseudo, max(score) as bestScore from PARTIES GROUP BY pseudo ORDER BY bestScore) where bestScore > (select max(score) from PARTIES where pseudo=?)");
        $req->bindParam(1, $pseudo);
        $req->execute();
        $result = $req->fetch(PDO::FETCH_ASSOC);
        return $result != null ? $result["rank"] : 0;
    }

    public function getNbPlayers(){
        $req = $this->db->query("select count(pseudo) as rank from (select pseudo, max(score) as bestScore from PARTIES GROUP BY pseudo ORDER BY bestScore)");
        $result = $req->fetch();
        return $result[0];
    }

    //retourne le pseudo et le meilleur score des $number premiers joueurs
    public function getLeaderboard($number)
    {
        $req = $this->db->prepare("select pseudo, max(score) as bestScore from PARTIES GROUP BY pseudo ORDER BY bestScore DESC LIMIT 0,?");
        $req->bindParam(1, $number);
        $req->execute();
        return $req->fetchAll();
    }

    //retourne le meilleur score d'un joueur
    public function getBestScore($pseudo)
    {
        $req = $this->db->prepare("select max(score) from PARTIES where pseudo=?");
        $req->bindParam(1, $pseudo);
        $req->execute();
        $result = $req->fetch();
        return $result != null ? $result[0] : 0;
    }

    //retourne l'id de la partie en cours d'un joueur
    public function getId($pseudo): int
    {
        $statement = $this->db->prepare("select id from PARTIES where pseudo=? and gagne=0;");
        $statement->bindParam(1, $pseudo);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result != null ? $result["id"] : 0;
    }

    //score de la partie
    public function getScore($id): int
    {
        $statement = $this->db->prepare("select score from PARTIES where id=?;");
        $statement->bindParam(1, $id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result != null ? $result["score"] : 0;
    }
    public function setScore($id, $score)
    {
        $statement = $this->db->prepare("update PARTIES set score=? where id=?;");
        $statement->bindParam(1, $score);
        $statement->bindParam(2, $id);
        $statement->execute();
        //$result = $statement->fetch(PDO::FETCH_ASSOC);
    }

    //état de la partie
    public function getEtat($id): int
    {
        $statement = $this->db->prepare("select gagne from PARTIES where id=?;");
        $statement->bindParam(1, $id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result["gagne"];
    }
    public function setEtat($etat, $id)
    {
        $statement = $this->db->prepare("update PARTIES set gagne=? where id=?;");
        $statement->bindParam(1, $etat);
        $statement->bindParam(2, $id);
        $statement->execute();
    }

    //retourne le nombre de parties d'un joueur
    public function getGames($pseudo){
        $statement = $this->db->prepare("select count(*) from PARTIES where pseudo=?");
        $statement->bindParam(1, $pseudo);
        $statement->execute();
        $result = $statement->fetch();
        return $result[0];
    }

    //retourne le nombre de parties gagnées d'un joueur
    public function getWinGames($pseudo){
        $statement = $this->db->prepare("select count(*) from PARTIES where pseudo=? and gagne=2");
        $statement->bindParam(1, $pseudo);
        $statement->execute();
        $result = $statement->fetch();
        return $result[0];
    }
}