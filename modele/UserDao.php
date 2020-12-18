<?php
require_once PATH_METIER . "/User.php";
require_once "BDException.php";
require_once "SqliteConnexion.php";

class UserDao
{

    // ajouter le(s) attribut(s)
    private $connexion;

    public function __construct()
    {
        $this->connexion = SqliteConnexion::getInstance()->getConnexion();
    }

    //retourne si le joueur existe ou non
    public function exists(string $pseudo): bool
    {
        try {
            $statement = $this->connexion->prepare("select pseudo from JOUEURS where pseudo=?;");
            $statement->bindParam(1, $pseudo);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            return $result != null;
        } catch (PDOException $e) {
            throw new SQLException("problème requête SQL sur la table utilisateurs");

        }
    }

    //retourne si le mot de passe est correct
    public function verifierMdp(string $pseudo, string $pwd): bool
    {
        try {
            $statement = $this->connexion->prepare("select password from JOUEURS where pseudo=?;");
            $statement->bindParam(1, $pseudo);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            return password_verify($pwd, $result["password"]);
        } catch (PDOException $e) {
            throw new SQLException("problème requête SQL sur la table utilisateurs");

        }
    }

    //créer un joueur
    public function add(string $pseudo, string $pwd): void
    {
        $req = $this->connexion->prepare("INSERT INTO JOUEURS(pseudo, password) VALUES (:pseudo, :password)");
        $req->execute(array(
            "pseudo" => $pseudo,
            "password" => $pwd
        ));
    }
}

?>