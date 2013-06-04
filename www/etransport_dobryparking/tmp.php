<?php
error_reporting(E_ALL ^ E_NOTICE);
chdir(dirname(__FILE__)); // for Drag & Drop
require('../shared/php/functions.php');
require('../shared/php/simple_html_dom.php');
require('../shared/php/templates.php');
require('../shared/php/zip.php');


$cfg = array(
	'offline_mode'		=> $argv[1]=='offline',
	'offline_file'		=> 'offline.dat', // for [offline_mode] use this serialized array($cfg,$json,$pois,$countries) instead
	'title'				=> 'eTransport.pl',
	'subtitle'			=> 'Dobry Parking MIX',
	'tmp_dir'			=> '../shared/tmp/',
	'gpx_round'			=> 6, // Max. il. miejsc po przecinku w koordynatach GPS. Dotyczy: pliki HTM, GPX - tylko w <bounds>
	'gpsbabel'			=> '"C:\Program Files\GPSBabel\gpsbabel.exe"',
	'gpx_file'			=> 'ork_parking_MIX',
	'items_per_tpl'		=> 20,
	'tpl_file_mask'		=> 'ork_parking_MIX.[%1$s]%2$s.htm',
	'tpl_sub_dir'		=> 'ork_parking/',
	'zip_src_mask'		=> 'ork_parking_MIX_%s/',
	'zip_file_mask'		=> 'ork_parking_MIX.%s.zip',
	'map_url'			=> 'http://maps.google.com/maps?q=%1$s+%2$s',
	'map_url_static'	=> 'http://maps.googleapis.com/maps/api/staticmap?center=%1$f,%2$f&zoom=%3$d&size=640x640&scale=1&maptype=satellite&format=jpg&sensor=false',
	'map_url_static'	=> 'http://maps.googleapis.com/maps/api/staticmap?center=%1$f,%2$f&zoom=%3$d&size=640x640&scale=1&maptype=satellite&format=jpg&sensor=false&markers=size:mid%%7Ccolor:blue%%7Clabel:P%%7C%1$f,%2$f',
	'map_zoom'			=> 16, // Domyślny zoom zdjęcia
	'map_dir'			=> '../shared/map/',
	'map_sub_dir'		=> 'map/',
	'map_file_mask'		=> 'staticmap.[%1$05d].%2$s',
	'map_orph_mask'		=> '#\[(\d+)\]#', // wyciągnij numer POI z nazwy pliku
	'map_file_size'		=> 10000, // Rozmiar w bajtach pustego zdjęcia mapy
	'map_delorphan'		=> true, // Usuń obrazy map bez powiązanego POI
	'map_download'		=> true, // Pobieraj mapy z google
	'map_redownload'	=> false, // Odśwież wszystkie pliki map
	'map_append'		=> true, // Dołączaj zdjęcia google do plików HTM
	'count_interval'	=> 10, // Ilość zapytań w jednej serii
	'time_interval'		=> 2, // Odstęp (sek.) między każdą serią zapytań
	'logg_usleep'		=> 100000, // Odstęp (mikrosek.) między każdym logiem
	'log'				=> '', // Wygenerowany log
);
$json2cli = array(
	'count_countries'		=> 'Kraje',
	'count_parking'			=> 'Parkingi zwykłe',
	'count_parking_wifi'	=> 'Parkingi Wi-Fi',
	'count_parking_all'		=> 'Parkingi wszystkie',
	'requests'				=> 'Wysłane zapytania',
	'req_data'				=> 'Pobrane dane',
	'map_count'				=> 'Pobrane mapy',
	'map_data'				=> 'Rozmiar map',
	'map_miss'				=> 'Brakujące mapy', // hdd
	'map_fail'				=> 'Niedostępne mapy', // www
	'map_orph'				=> 'Usunięte mapy',
);

	Logg("Tryb [offline] jest aktywny!");
	if($tmp = @file_get_contents($cfg['offline_file'])) $tmp = unserialize($tmp);
	else {
		Logg("Nie znalazłem pliku [{$cfg['offline_file']}] Wychodzę...");
		exit;
	}
	$dat = $tmp['dat'];
	$json = $tmp['json'];
	$pois = $tmp['pois'];
	$countries = $tmp['countries'];
$cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
$cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);
$cfg['tpl_out_path'] = $cfg['zip_src_path'].$cfg['tpl_sub_dir'];
$cfg['map_out_path'] = $cfg['tpl_out_path'].$cfg['map_sub_dir'];
// ========================


		Logg('Usuwam osierocone mapy:');
		
		$matches = array();
		$orphans = array();
		$dir = opendir($cfg['map_dir']);
		while(false !== ($fname = readdir($dir))) {
			if(($fname=='.') || ($fname=='..') || is_dir($fname) || !preg_match($cfg['map_orph_mask'], $fname, $matches)) continue;
			if(!array_key_exists((int)$matches[1], $pois)) $orphans[] = $fname;
		}
		foreach($orphans as $fname) {
			chmod($cfg['map_dir'].$fname, 0666);
			unlink($cfg['map_dir'].$fname);
			$json['map_orph']++;
			Logg("$fname");
		}

exit;

// ========================
	Logg("Zapisuję pobrane dane do [{$cfg['offline_file']}]");
	file_put_contents($cfg['offline_file'], serialize(array(
		'dat' => $dat,
		'json' => $json,
		'pois' => $pois,
		'countries' => $countries,
	)));

	Logg('');
	Logg('Czas skryptu: '.FormatTime(get_execution_time()));
	RenderSettings();
