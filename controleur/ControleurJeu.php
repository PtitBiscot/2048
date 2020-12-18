<?php
require_once PATH_VUE . "/Vue.php";
require_once PATH_MODELE . "/GameDAO.php";
require_once PATH_METIER . "/Game.php";

class ControleurJeu
{
    private $vue;
    private $gameDAO;

    function __construct()
    {
        $this->vue = new Vue();
        $this->gameDAO = new GameDAO();
    }

    function play(string $pseudo, string $direction)
    {
        //on définit toutes les variables de session quand le joueur se connecte
        $_SESSION["pseudo"] = $pseudo;
        $leaderboard = $this->gameDAO->getLeaderboard(10);
        $_SESSION["leaderboard"] = $leaderboard;
        $id = $this->gameDAO->getId($_SESSION["pseudo"]);
        $_SESSION["bestScore"] = $this->gameDAO->getBestScore($pseudo);
        $_SESSION["rank"] = $this->gameDAO->getPosition($pseudo);
        $_SESSION["nbPlayers"] = $this->gameDAO->getNbPlayers();
        $_SESSION["Games"] = $this->gameDAO->getGames($pseudo);
        $_SESSION["wonGames"] = $this->gameDAO->getWinGames($pseudo);
        $games = null;
        $won = null;
        for ($cpt = 0; $cpt < sizeof($leaderboard); $cpt++) {
            $games[$cpt] = $this->gameDAO->getGames($leaderboard[$cpt][0]);
            $won[$cpt] = $this->gameDAO->getWinGames($leaderboard[$cpt][0]);
        }
        $_SESSION["GamesOthers"] = $games;
        $_SESSION["wonGamesOthers"] = $won;

        //création d'une partie si il y en a pas en cours
        if ($id == 0) {
            $grille = array(
                array(0, 0, 0, 0),
                array(0, 0, 0, 0),
                array(0, 0, 0, 0),
                array(0, 0, 0, 0)
            );
            //initialisation des deux premières cases
            try {
                $row_random1 = random_int(0, 3);
                $col_random1 = random_int(0, 3);
                do {
                    $row_random2 = random_int(0, 3);
                    $col_random2 = random_int(0, 3);
                } while ($row_random1 == $row_random2 && $col_random1 == $col_random2);
                $grille[$row_random1][$col_random1] = 2;
                $grille[$row_random2][$col_random2] = 2;
            } catch (Exception $e) {
            }
            $game = new Game($pseudo);
            $this->gameDAO->insert($game);
            $_SESSION["grille"] = $grille;
            $_SESSION["score"] = "0";
            $_SESSION["bestScore"] = $this->gameDAO->getBestScore($pseudo);
            $_SESSION["rank"] = $this->gameDAO->getPosition($pseudo);
            //les cookies servent de stockage si le joueur se déconnecte
            setcookie($pseudo . "grille", json_encode($_SESSION["grille"]), time() + 365 * 24 * 3600);
            setcookie($pseudo . "score", $_SESSION["score"], time() + 365 * 24 * 3600);
            setcookie($pseudo . "grille_precedente", json_encode($_SESSION["grille"]), time() + 365 * 24 * 3600);
            setcookie($pseudo . "score_precedent", $_SESSION["score"], time() + 365 * 24 * 3600);
            setcookie($pseudo . "precedent", false, time() + 365 * 24 * 3600);
            $this->vue->game();
        } //si une partie est en cours
        else {
            //on stocke l'ancienne grille et l'ancien score
            $grille_precedente = json_decode($_COOKIE[$pseudo . "grille_precedente"], true);
            $score_precedent = $_COOKIE[$pseudo . "score_precedent"];
            if (!isset($_GET["precedent"]) || $_GET["precedent"] != true) {
                $_SESSION["grille"] = json_decode($_COOKIE[$pseudo . "grille"], true);
                $_SESSION["score"] = $_COOKIE[$pseudo . "score"];
                setcookie($pseudo . "grille_precedente", json_encode($_SESSION["grille"]), time() + 365 * 24 * 3600);
                setcookie($pseudo . "score_precedent", $_COOKIE[$pseudo . "score"], time() + 365 * 24 * 3600);
                if ($direction == "rien") {
                    $this->vue->game();
                } else {
                    $l = 0;
                    //déplacement et additionnement des cases
                    switch ($direction) {
                        case "haut":
                            $k1 = $this->retasseHaut($_SESSION["grille"], 1);
                            $k2 = $this->additionneHaut($_SESSION["grille"], 1);
                            $k3 = $this->retasseHaut($_SESSION["grille"], 1);
                            $id = $this->gameDAO->getId($_SESSION["pseudo"]);
                            $this->gameDAO->setScore($id, ($this->gameDAO->getScore($id) + $k2));
                            $l = $k1 + $k2 + $k3;
                            break;
                        case "gauche":
                            $k1 = $this->retasseGauche($_SESSION["grille"], 1);
                            $k2 = $this->additionneGauche($_SESSION["grille"], 1);
                            $k3 = $this->retasseGauche($_SESSION["grille"], 1);
                            $id = $this->gameDAO->getId($_SESSION["pseudo"]);
                            $this->gameDAO->setScore($id, ($this->gameDAO->getScore($id) + $k2));
                            $l = $k1 + $k2 + $k3;
                            break;
                        case "bas":
                            $k1 = $this->retasseBas($_SESSION["grille"], 1);
                            $k2 = $this->additionnebas($_SESSION["grille"], 1);
                            $k3 = $this->retasseBas($_SESSION["grille"], 1);
                            $id = $this->gameDAO->getId($_SESSION["pseudo"]);
                            $this->gameDAO->setScore($id, ($this->gameDAO->getScore($id) + $k2));
                            $l = $k1 + $k2 + $k3;
                            break;
                        case "droite":
                            $k1 = $this->retasseDroite($_SESSION["grille"], 1);
                            $k2 = $this->additionneDroite($_SESSION["grille"], 1);
                            $k3 = $this->retasseDroite($_SESSION["grille"], 1);
                            $id = $this->gameDAO->getId($_SESSION["pseudo"]);
                            $this->gameDAO->setScore($id, ($this->gameDAO->getScore($id) + $k2));
                            $l = $k1 + $k2 + $k3;
                            break;
                    }
                    //apparition d'une cellule à un endroit libre aléatoire si l'action a provoqaué au moins un déplacement ou un additionnement
                    $dispo = null;
                    if ($l > 0) {
                        $cpt = 0;
                        for ($row = 0; $row < 4; $row++) {
                            for ($col = 0; $col < 4; $col++) {
                                if ($_SESSION["grille"][$row][$col] == 0) {
                                    $dispo[$cpt][0] = $row;
                                    $dispo[$cpt][1] = $col;
                                    $cpt++;
                                }
                            }
                        }
                        try {
                            $cell_random = random_int(0, sizeof($dispo) - 1);
                            $value_random = random_int(1, 2);
                            //conservation de l'emplacement et de la valeur de la cellule si le joueur fait un coup précédent suivi d'une même direction
                            if (isset($_COOKIE[$pseudo . "precedent"]) && $_COOKIE[$pseudo . "precedent"]) {
                                $_SESSION["grille"][$dispo[$_COOKIE[$pseudo . "cell_random"]][0]][$dispo[$_COOKIE[$pseudo . "cell_random"]][1]] = $_COOKIE[$pseudo . "value_random"] * 2;
                                setcookie($pseudo . "precedent", false, time() + 365 * 24 * 3600);
                            } else {
                                $_SESSION["grille"][$dispo[$cell_random][0]][$dispo[$cell_random][1]] = $value_random * 2;
                                setcookie($pseudo . "cell_random", $cell_random, time() + 365 * 24 * 3600);
                                setcookie($pseudo . "value_random", $value_random, time() + 365 * 24 * 3600);
                            }
                        } catch (Exception $e) {
                        }
                        //vérification que la partie est terminé après avoir joué
                        if (sizeof($dispo) - 1 == 0) {

                            $h1 = $this->retasseHaut($_SESSION["grille"], 0);
                            $h2 = $this->additionneHaut($_SESSION["grille"], 0);
                            $h3 = $this->retasseHaut($_SESSION["grille"], 0);
                            $g1 = $this->retasseGauche($_SESSION["grille"], 0);
                            $g2 = $this->additionneGauche($_SESSION["grille"], 0);
                            $g3 = $this->retasseGauche($_SESSION["grille"], 0);
                            $b1 = $this->retasseBas($_SESSION["grille"], 0);
                            $b2 = $this->additionnebas($_SESSION["grille"], 0);
                            $b3 = $this->retasseBas($_SESSION["grille"], 0);
                            $d1 = $this->retasseDroite($_SESSION["grille"], 0);
                            $d2 = $this->additionneDroite($_SESSION["grille"], 0);
                            $d3 = $this->retasseDroite($_SESSION["grille"], 0);
                            if ($h1 + $h2 + $h3 + $g1 + $g2 + $g3 + $b1 + $b2 + $b3 + $d1 + $d2 + $d3 == 0) {
                                $_SESSION["leaderboard"] = $this->gameDAO->getLeaderboard(20);
                                setcookie($pseudo . "grille", "", time() - 3600);
                                setcookie($pseudo . "score", "", time() - 3600);
                                setcookie($pseudo . "grille_precedente", "", time() - 3600);
                                setcookie($pseudo . "score_precedent", "", time() - 3600);
                                setcookie($pseudo . "precedent", "", time() - 3600);
                                $grille = $_SESSION["grille"];
                                $gagne = false;
                                for ($i=0; $i<4; $i++){
                                    for ($j=0; $j<4; $j++){
                                        if($grille[$i][$j] >= 2048) $gagne=true;
                                    }
                                }
                                $gagne == false ? $this->gameDAO->setEtat(1, $id) : $this->gameDAO->setEtat(2, $id);
                                $_SESSION["gagne"] = $gagne;
                                $this->vue->resultat();
                                exit(0);
                            }
                        }
                    } //si le coup n'a pas eu d'effet on conserve la grille et le score précédent pour un éventuel coup précédent
                    else {
                        setcookie($pseudo . "grille_precedente", json_encode($grille_precedente), time() + 365 * 24 * 3600);
                        setcookie($pseudo . "score_precedent", $score_precedent, time() + 365 * 24 * 3600);
                    }
                    //on actualise les données de session directement après le coup
                    setcookie($pseudo . "grille", json_encode($_SESSION["grille"]), time() + 365 * 24 * 3600);
                    $score = $this->gameDAO->getScore($id);
                    $_SESSION["score"] = $score;
                    $_SESSION["bestScore"] = $this->gameDAO->getBestScore($pseudo);
                    $_SESSION["rank"] = $this->gameDAO->getPosition($pseudo);
                    $leaderboard = $this->gameDAO->getLeaderboard(10);
                    for ($cpt = 0; $cpt < sizeof($leaderboard); $cpt++) {
                        $games[$cpt] = $this->gameDAO->getGames($leaderboard[$cpt][0]);
                        $won[$cpt] = $this->gameDAO->getWinGames($leaderboard[$cpt][0]);
                    }
                    $_SESSION["leaderboard"] = $leaderboard;
                    $_SESSION["GamesOthers"] = $games;
                    $_SESSION["wonGamesOthers"] = $won;
                    setcookie($pseudo . "score", $_SESSION["score"], time() + 365 * 24 * 3600);
                    $this->vue->game();
                }
            } //coup précédent
            else {
                $_SESSION["grille"] = $grille_precedente;
                $_SESSION["score"] = $score_precedent;
                $this->gameDAO->setScore($id, $score_precedent);
                $_SESSION["leaderboard"] = $this->gameDAO->getLeaderboard(10);
                $_SESSION["bestScore"] = $this->gameDAO->getBestScore($pseudo);
                $_SESSION["rank"] = $this->gameDAO->getPosition($pseudo);
                setcookie($pseudo . "grille", json_encode($grille_precedente), time() + 365 * 24 * 3600);
                setcookie($pseudo . "score", $score_precedent, time() + 365 * 24 * 3600);
                setcookie($pseudo . "grille_precedente", json_encode($grille_precedente), time() + 365 * 24 * 3600);
                setcookie($pseudo . "score_precedent", $score_precedent, time() + 365 * 24 * 3600);
                setcookie($pseudo . "precedent", true, time() + 365 * 24 * 3600);
                $this->vue->game();
            }
        }
    }

    private function retasseDroite($grille, $retour)
    {
        $l = 0;
        for ($i = 0; $i < 4; $i++) {
            $k = 3;
            for ($j = 3; $j >= 0; $j--)
                if ($grille[$i][$j] != 0) {
                    $grille[$i][$k] = $grille[$i][$j];
                    if ($k > $j) {
                        $grille[$i][$j] = 0;
                        $l = 1;
                    }
                    $k--;
                }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $l;
    }

    private function retasseGauche($grille, $retour)
    {
        $l = 0;
        for ($i = 0; $i < 4; $i++) {
            $k = 0;
            for ($j = 0; $j < 4; $j++)
                if ($grille[$i][$j] != 0) {
                    $grille[$i][$k] = $grille[$i][$j];
                    if ($k < $j) {
                        $grille[$i][$j] = 0;
                        $l = 1;
                    }
                    $k++;
                }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $l;
    }

    private function retasseHaut($grille, $retour)
    {
        $l = 0;
        for ($j = 0; $j < 4; $j++) {
            $k = 0;
            for ($i = 0; $i < 4; $i++)
                if ($grille[$i][$j] != 0) {
                    $grille[$k][$j] = $grille[$i][$j];
                    if ($k < $i) {
                        $grille[$i][$j] = 0;
                        $l = 1;
                    }
                    $k++;
                }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $l;
    }

    private function retasseBas($grille, $retour)
    {
        $l = 0;
        for ($j = 0; $j < 4; $j++) {
            $k = 3;
            for ($i = 3; $i >= 0; $i--)
                if ($grille[$i][$j] != 0) {
                    $grille[$k][$j] = $grille[$i][$j];
                    if ($k > $i) {
                        $grille[$i][$j] = 0;
                        $l = 1;
                    }
                    $k--;
                }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $l;
    }

    private function additionneDroite($grille, $retour)
    {
        $score = 0;
        for ($i = 0; $i < 4; $i++) {
            for ($j = 3; $j > 0; $j--) {
                if ($grille[$i][$j] == $grille[$i][$j - 1]) {
                    $grille[$i][$j] = $grille[$i][$j] + $grille[$i][$j - 1];
                    $score += $grille[$i][$j];
                    $grille[$i][$j - 1] = 0;
                }
            }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $score;
    }

    private function additionneGauche($grille, $retour)
    {
        $score = 0;
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 3; $j++) {
                if ($grille[$i][$j] == $grille[$i][$j + 1]) {
                    $grille[$i][$j] = $grille[$i][$j] + $grille[$i][$j + 1];
                    $score += $grille[$i][$j];
                    $grille[$i][$j + 1] = 0;
                }
            }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $score;
    }

    private function additionneHaut($grille, $retour)
    {
        $score = 0;
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 4; $j++) {
                if ($grille[$i][$j] == $grille[$i + 1][$j]) {
                    $grille[$i][$j] = $grille[$i][$j] + $grille[$i + 1][$j];
                    $score += $grille[$i][$j];
                    $grille[$i + 1][$j] = 0;
                }
            }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $score;
    }

    private function additionnebas($grille, $retour)
    {
        $score = 0;
        for ($i = 3; $i > 0; $i--) {
            for ($j = 0; $j < 4; $j++) {
                if ($grille[$i][$j] == $grille[$i - 1][$j]) {
                    $grille[$i][$j] = $grille[$i][$j] + $grille[$i - 1][$j];
                    $score += $grille[$i][$j];
                    $grille[$i - 1][$j] = 0;
                }
            }
        }
        if ($retour == 1) {
            $_SESSION["grille"] = $grille;
        }
        return $score;
    }

}