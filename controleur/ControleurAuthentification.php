<?php
require_once PATH_VUE . "/Vue.php";
require_once PATH_MODELE . "/UserDao.php";
require_once PATH_MODELE . "/GameDAO.php";
require_once PATH_METIER . "/User.php";
require_once PATH_CONTROLEUR . "/ControleurJeu.php";

class ControleurAuthentification
{
    private $vue;

    function __construct()
    {
        $this->vue = new Vue();

    }

    function accueil()
    {
        $this->vue->demandePseudo();
    }

    function connexion(string $pseudo, string $pwd)
    {
        $userDAO = new UserDao();
        //joueur authentifié
        if ($userDAO->exists($pseudo) && $userDAO->verifierMdp($pseudo, $pwd)) {
            $ctrlJeu = new ControleurJeu();
            $ctrlJeu->play($pseudo, "rien");
        }
        //joueur non authentifié
        else $this->vue->demandePseudo();
    }

    function inscription(string $pseudo, string $pwd)
    {
        $userDAO = new UserDao();
        //le joueur peut s'inscrire
        if (!$userDAO->exists($pseudo)) {
            $userDAO->add($pseudo, password_hash($pwd, PASSWORD_DEFAULT));
            $ctrlJeu = new ControleurJeu();
            $ctrlJeu->play($pseudo, "rien");
        }
        //le joueur existe déjà
        else $this->vue->demandePseudo();
    }

    function deconnexion()
    {
        session_destroy();
        header("Location: index.php");
    }

    function recommencer()
    {
        $gameDAO = new GameDAO();
        $pseudo = $_SESSION["pseudo"];
        $id = $gameDAO->getId($pseudo);
        setcookie($pseudo."grille", "", time() - 3600);
        setcookie($pseudo."score", "", time() - 3600);
        setcookie($pseudo."grille_precedente", "", time() - 3600);
        setcookie($pseudo."score_precedent", "", time() - 3600);
        setcookie($pseudo."precedent", "", time() - 3600);
        //On change l'attribue "gagne" à 1 si la partie est perdue, 2 si elle est gagnée
        $grille = $_SESSION["grille"];
        $gagne = false;
        for ($i=0; $i<4; $i++){
            for ($j=0; $j<4; $j++){
                if($grille[$i][$j] >= 2048) $gagne=true;
            }
        }
        $gagne == false ? $gameDAO->setEtat(1, $id) : $gameDAO->setEtat(2, $id);
//        header("Location: index.php");
        $_SESSION["gagne"] = $gagne;
        $this->vue->resultat();
    }
}