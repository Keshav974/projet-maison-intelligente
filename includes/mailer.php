<?php
// includes/mailer.php

// On importe les classes de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function send_email($to_email, $to_name, $subject, $html_message) {
    
    $mail = new PHPMailer(true); // "true" active les exceptions en cas d'erreur

    try {
        // Paramètres du serveur SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->Username   = 'vahsek1999@gmail.com';
        $mail->Password   = 'pcvycznkkliquakr';
        // ---------------------------------


        $mail->setFrom('vahsek1999@gmail.com', 'Ma Maison Intelligente'); 
        $mail->addAddress($to_email, $to_name);

        // Contenu de l'email
        $mail->isHTML(true); // On spécifie que l'email est au format HTML
        $mail->Subject = $subject;
        $mail->Body    = $html_message;
        $mail->AltBody = strip_tags($html_message); // Version texte brut pour les clients mail qui ne lisent pas le HTML

        // On envoie
        $mail->send();
        return true; // L'email a été envoyé avec succès

    } catch (Exception $e) {

        return false; // L'envoi a échoué
    }
}