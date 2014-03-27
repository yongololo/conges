<?php
/*
Planning Biblio, Plugin Congés Version 1.4.9
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.md et LICENSE
Copyright (C) 2013-2014 - Jérôme Combes

Fichier : plugins/conges/ajax.enregistreRecup.php
Création : 11 octobre 2013
Dernière modification : 27 février 2014
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Enregistre la demande de récupération
*/

session_start();

$version="1.4.5";
include "../../include/config.php";

ini_set('display_errors',$config['display_errors']);
switch($config['error_reporting']){
  case 0: error_reporting(0); break;
  case 1: error_reporting(E_ERROR | E_WARNING | E_PARSE); break;
  case 2: error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); break;
  case 3: error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); break;
  case 4: error_reporting(E_ALL ^ E_NOTICE); break;
  case 5: error_reporting(E_ALL); break;
  default: error_reporting(E_ALL ^ E_NOTICE); break;
}

include "../../include/function.php";
include "../../personnel/class.personnel.php";
include "class.conges.php";

// Les dates sont au format DD/MM/YYYY et converti en YYYY-MM-DD
$date=dateFr($_GET['date']);
$date2=dateFr($_GET['date2']);
$perso_id=is_numeric($_GET['perso_id'])?$_GET['perso_id']:$_SESSION['login_id'];

$insert=array("perso_id"=>$perso_id,"date"=>$date,"date2"=>$date2,"heures"=>$_GET['heures'],"commentaires"=>$_GET['commentaires'],
  "saisie_par"=>$_SESSION['login_id']);
$db=new db();
$db->insert2("recuperations",$insert);
if($db->error){
  echo "###Demande-Erreur###";
}
else{
  echo "###Demande-OK###";

  // Envoi d'un e-mail à l'agent et aux responsables
  $p=new personnel();
  $p->fetchById($perso_id);
  $nom=$p->elements[0]['nom'];
  $prenom=$p->elements[0]['prenom'];
  $mail=$p->elements[0]['mail'];
  $mailResponsable=$p->elements[0]['mailResponsable'];

  $c=new conges();
  $c->getResponsables($date,$date,$perso_id);
  $responsables=$c->responsables;

  // Choix des destinataires en fonction de la configuration
  $destinataires=array();
  switch($config['Absences-notifications']){
    case 1 :
      foreach($responsables as $elem){
	$destinataires[]=$elem['mail'];
      }
      break;
    case 2 :
      $destinataires[]=$mailResponsable;
      break;
    case 3 :
      $destinataires=explode(";",$config['Mail-Planning']);
      break;
    case 4 :
      $destinataires=explode(";",$config['Mail-Planning']);
      $destinataires[]=$mail;
      $destinataires[]=$mailResponsable;
      foreach($responsables as $elem){
	$destinataires[]=$elem['mail'];
      }
      break;
    case 5 :
      $destinataires[]=$mail;
      break;
  }

  if(!empty($destinataires)){
    $sujet="Nouvelle demande de récupération";
    $message="Demande de récupération du ".dateFr($date)." enregistrée pour $prenom $nom<br/><br/>";
    if($_GET['commentaires']){
      $message.="Commentaires : ".str_replace("\n","<br/>",$_GET['commentaires']);
    }
    sendmail($sujet,$message,$destinataires);
  }
}
?>