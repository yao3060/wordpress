<?php

/*
Plugin Name: PHPMailer Init
Description: initialize the phpmailer.
Author: YAO
Version: 1.0
Author URI: https://www.yaoyingying.com
*/

use PHPMailer\PHPMailer\PHPMailer;

function my_mailtrap(PHPMailer $phpmailer)
{
    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.mailtrap.io';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 2525;
    $phpmailer->Username = 'ffb93b661dd620';
    $phpmailer->Password = '8b32e864343dab';
}

add_action('phpmailer_init', 'my_mailtrap');
