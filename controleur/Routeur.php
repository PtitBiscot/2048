<?php
require_once 'ControleurAuthentification.php';
require_once 'ControleurJeu.php';
session_start();

class Routeur
{
    private $ctrlAuthentification;
    private $ctrlJeu;

    public function __construct()
    {
        $this->ctrlAuthentification = new ControleurAuthentification();
        $this->ctrlJeu = new controleurJeu();
    }

    public function routerRequete()
    {
        //bouton deconnexion
        if ((isset($_GET["deconnexion"]) && $_GET["deconnexion"] == true) || (isset($_GET["game-end-deconnexion"]) && $_GET["game-end-deconnexion"] == true)) {
            $this->ctrlAuthentification->deconnexion();
        }
        //bouton recommencer une partie
        else if (isset($_GET["recommencer"]) && $_GET["recommencer"] == true) {
            $this->ctrlAuthentification->recommencer();
        }
        //le joueur est connecté, il continue de jouer
        else if (isset($_SESSION["pseudo"])) {
            $this->ctrlJeu->play($_SESSION["pseudo"], (isset($_GET["direction"])) ? $_GET["direction"] : "rien");
        }
        //connexion
        else if (isset($_POST["connexion"], $_POST["pseudo"], $_POST["pwd"]) && !empty($_POST["pseudo"]) && !empty($_POST["pwd"])) {
            $this->ctrlAuthentification->connexion($_POST["pseudo"], $_POST["pwd"]);
        }
        //inscription
        else if (isset($_POST["inscription"], $_POST["pseudo"], $_POST["pwd"]) && !empty($_POST["pseudo"]) && !empty($_POST["pwd"])) {
            $this->ctrlAuthentification->inscription($_POST["pseudo"], $_POST["pwd"]);
        }
        //sinon afficher fenêtre de connexion
        else {
            $this->ctrlAuthentification->accueil();
        }
    }
}