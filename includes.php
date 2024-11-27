<?php


include('inc/class-wcpi-core.php');
include('inc/class-wcpi-plugin.php');
include('inc/class-wcpi-settings.php');

if ( class_exists( 'WC_Email') ) {
	include('inc/class-wcpi-email.php');
}