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

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/akcije/lista/

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/vrsteplacanja/list

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/grupeartikala/lista

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/robnemarke/lista

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/skladista/lista
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/skladista/sifra/101

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/vrsteplacanja/list

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/naziv/
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/uid/61259-2987

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/StanjeZalihe/Skladiste/101
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/StanjeZalihe/Skladiste/[101,001]/9150032160

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/partneri/email/ljubica.polimac@amds.hr

$url      = 'http://sechip.dyndns.org:8889/datasnap/rest/artikli/sifra/9504000452';
$username = 'webshop';
$password = 'test.bJ8tn63Q';
$ch       = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

//$response = json_encode($response, JSON_PRETTY_PRINT);
header('Content-Type: application/json');
//echo 'Poziv: <span style="color: darkolivegreen">' . $url . '</span><br><hr><br>';

//echo $url;
echo $response;

if (curl_errno($ch)) {
    echo curl_error($ch);

}







