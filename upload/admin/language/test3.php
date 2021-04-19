<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ip_server = $_SERVER['SERVER_ADDR'];

// Printing the stored address
//echo "Server IP Address is: $ip_server";

//echo'<br>';

// TEST :: http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/


// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/lista/[0,40]
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/sifra/P12052
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/atribut/atribut_uid/59-2987
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/dokumenti/37107-2987

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/grupeartikala/lista

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/vrsteplacanja/list

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/naziv/
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/uid/61259-2987

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/StanjeZalihe/Skladiste/P04

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/partneri/email/ljubica.polimac@amds.hr

$url = 'http://luceedapi.tomsoft.hr:3675/datasnap/rest/StanjeZalihe/ArtiklUID/1022004-2987/P04';
$username = 'webshop_prehrana';
$password = 'i8Qhb152';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

echo 'Poziv: <span style="color: darkolivegreen">' . $url . '</span><br><hr><br>';
echo $response;

if(curl_errno($ch)){
    echo curl_error($ch);

}

?>
