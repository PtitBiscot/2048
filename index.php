<?php

require "config/config.php";
require PATH_CONTROLEUR . "/Routeur.php";

$routeur = new Routeur();
$routeur->routerRequete();

?>