<?php

// sending POST-request from script

if(isset($_POST["xmlmsg"])) {

	include ($_SERVER['DOCUMENT_ROOT'] . 'wp-load.php');

	$kapital = new WC_Gateway_Kapitalbank();

	$data = simplexml_load_string(stripslashes($_POST['xmlmsg']));

    $kapital->check_Kapitalbank_response($data);
}

