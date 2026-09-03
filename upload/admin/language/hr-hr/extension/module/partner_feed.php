<?php
$_['heading_title'] = 'Luceed partneri i XML feedovi';

$_['text_extension'] = 'Moduli';
$_['text_home'] = 'Početna stranica';
$_['text_list'] = 'Luceed partneri i XML feedovi';
$_['text_add'] = 'Dodaj partnera';
$_['text_edit'] = 'Uredi partnera';
$_['text_enabled'] = 'Uključeno';
$_['text_disabled'] = 'Isključeno';
$_['text_yes'] = 'Da';
$_['text_no'] = 'Ne';
$_['text_missing_group'] = 'Grupa ne postoji';
$_['text_never'] = 'Nikad';
$_['text_copy'] = 'Kopiraj XML URL';
$_['text_create_group'] = '-- Kreiraj novu grupu kupaca --';
$_['text_success'] = 'Partner je uspješno spremljen.';
$_['text_success_delete'] = 'Partner je uklonjen iz modula. Grupa kupaca i cijene nisu obrisane.';
$_['text_success_key'] = 'Novi pristupni ključ je generiran. Prethodni XML URL više ne vrijedi.';
$_['text_success_sync'] = 'Sinkronizirano je %s cijena za partnera %s.';
$_['text_success_sync_all'] = 'Sinkronizirano je ukupno %s partnerskih cijena.';
$_['text_partial_sync'] = 'Sinkronizirano je %s cijena. Partneri s greškom zadržali su prethodne cijene: %s';
$_['text_confirm_delete'] = 'Ukloniti partnera iz modula? Grupa kupaca neće biti obrisana.';
$_['text_confirm_key'] = 'Generirati novi ključ? Postojeći XML URL odmah prestaje vrijediti.';
$_['text_confirm_sync'] = 'Pokrenuti Luceed sinkronizaciju?';

$_['column_name'] = 'Partner';
$_['column_luceed_id'] = 'Luceed ID kupca';
$_['column_customer_group'] = 'OpenCart grupa';
$_['column_prices'] = 'Cijene';
$_['column_sync'] = 'Luceed sinkronizacija';
$_['column_feed'] = 'XML feed';
$_['column_last_sync'] = 'Zadnja sinkronizacija';
$_['column_action'] = 'Akcija';

$_['entry_name'] = 'Naziv partnera';
$_['entry_code'] = 'Kod feeda';
$_['entry_luceed_partner_id'] = 'Luceed ID kupca';
$_['entry_customer_group'] = 'Grupa kupaca';
$_['entry_new_customer_group'] = 'Naziv nove grupe';
$_['entry_status'] = 'Luceed sinkronizacija';
$_['entry_feed_status'] = 'XML feed';
$_['entry_sort_order'] = 'Redoslijed';

$_['help_code'] = 'Koristi se u XML URL-u. Ako ostane prazno, generira se iz naziva.';
$_['help_luceed_partner_id'] = 'ID mora ostati tekst, npr. 00983204 ili 14956-N1.';
$_['help_customer_group'] = 'Odaberite postojeću grupu ili kreirajte novu. Više feedova može koristiti istu grupu i tada dijele cijene spremljene u product_discount te grupe.';
$_['help_feed_status'] = 'XML sadrži samo proizvode koji imaju cijenu za odabranu grupu i quantityws veći od nule.';

$_['button_sync_all'] = 'Sinkroniziraj sve';
$_['button_sync'] = 'Sinkroniziraj partnera';
$_['button_regenerate'] = 'Generiraj novi ključ';

$_['error_permission'] = 'Nemate ovlasti za promjenu Luceed partnera i XML feedova.';
$_['error_name'] = 'Naziv mora sadržavati između 2 i 128 znakova.';
$_['error_code'] = 'Kod feeda nije ispravan.';
$_['error_code_exists'] = 'Taj kod feeda već postoji.';
$_['error_luceed_partner_id'] = 'Luceed ID kupca je obavezan.';
$_['error_luceed_partner_exists'] = 'Taj Luceed ID kupca već je dodan.';
$_['error_customer_group'] = 'Odaberite grupu kupaca ili unesite naziv nove grupe.';
$_['error_new_customer_group'] = 'Naziv nove grupe mora sadržavati između 2 i 32 znaka.';
$_['error_customer_group_missing'] = 'Odabrana grupa kupaca više ne postoji.';
$_['error_not_found'] = 'Partner nije pronađen.';
$_['error_sync'] = 'Luceed sinkronizacija nije uspjela: %s';
