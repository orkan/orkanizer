<?php

/**
 * eTransport.pl Dobry Parking MIX
 * http://etransport.pl/dobry_parking
 *
 * Wyciąga ze stron informacje o wszystkich POI
 * Tworzy plik [gpx] i [ov2]
 * Generuje strony [htm] ze szczegółami każdego POI
 */

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

$dat = array( // dane w pliku offline
	'host_url'			=> 'http://etransport.pl/',
	'country_url'		=> array('dobry_parking', 'dobry_parking_z_wifi'),
	'cdate'				=> date('YmdHis', time()),
	'year'				=> date('Y'),
	'date'				=> date('Y-m-d'),
	'date_long'			=> date('Y-m-d H:i:s'),
	'date_atom'			=> date(DATE_ATOM),
	'gpx_minlat'		=> floatval('9223372036854775807'),
	'gpx_minlon'		=> floatval('9223372036854775807'),
	'gpx_maxlat'		=> floatval('-9223372036854775808'),
	'gpx_maxlon'		=> floatval('-9223372036854775808'),
);

$json = array( // dane w pliku offline
	'count_countries'		=> 0,
	'count_parking'			=> 0,
	'count_parking_wifi'	=> 0,
	'count_parking_all'		=> 0,
	'requests'				=> 0, // Ilość wykonanych zapytań
	'req_data'				=> 0, // Ilość pobranych danych
	'map_count'				=> 0,
	'map_data'				=> 0,
	'map_miss'				=> 0,
	'map_fail'				=> 0,
	'map_orph'				=> 0,
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
	'map_miss'				=> 'Brakujące mapy', // Brakujące mapy
	'map_fail'				=> 'Niedostępne mapy', // Niedostępne mapy np. zły rozmiar (patrz: $cfg['map_file_size']) albo "We have no imaginery here"
	'map_orph'				=> 'Usunięte mapy', // Niepotrzebne obrazy map po usuniętych POI
);


// ========================
// START :: Here !!!
// ========================
Logg("Witaj na {$cfg['title']}, {$cfg['subtitle']}!");
Logg('Aktualny czas serwera: '.date('Y-m-d H:i:s'));
Logg('');

if($cfg['offline_mode'])
{
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
}

// ========================
// Te dane $cfg trzeba zmixować z $dat niestety
$cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
$cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);
$cfg['tpl_out_path'] = $cfg['zip_src_path'].$cfg['tpl_sub_dir'];
$cfg['map_out_path'] = $cfg['tpl_out_path'].$cfg['map_sub_dir'];


if(!$cfg['offline_mode'])
{
	$pois = array();
	$countries = array();
	foreach($dat['country_url'] as $url)
	{
		// ========================
		// Lista krajów (zwykłe, wifi)
		// ========================
		Logg('');
		Logg("Pobieram listę krajów: [{$dat['host_url']}$url]");
		$html = GetFile($dat['host_url'].$url,1);

		if(!$html) {
			Logg("URL nieosiągalny! Kontynuuję...");
			continue;
		}

		$wifi = strpos($url,'wifi');
		$countrymap = array();
		$json['count_countries_tmp'] += $json['count_countries'];

		foreach($html->find('select[name=kraj_id]',0)->children as $option) {
			if($wifi && strpos($option->value,'wifi')===false) continue; // Na liście [dobre_parkingi_z_wifi] są dwa urle dla Polska: [dobre_parkingi,Polska,151] i [dobre_parkingi_z_wifi,polska,151]
			$i = crc32($option->plaintext);
			$countrymap[$i]['key']	= $i;
			$countrymap[$i]['name']	= $option->plaintext;
			$countrymap[$i]['ccONZ']= GetCountryONZ($option->plaintext);
			$countrymap[$i]['ccISO']= $ccONZ2ISO[$countrymap[$i]['ccONZ']][0];
			$countrymap[$i]['url']	= $option->value;
			$countrymap[$i]['ids']	= is_array($countries[$i]['ids']) ? $countries[$i]['ids'] : array();
			$countries[$i] = $countrymap[$i];
			$json['count_countries']++;
		}

		Logg("Znalazłem ".count($countrymap)." kraje (z ".($json['count_countries']-$json['count_countries_tmp'])." pozycji)");
		Logg('');

		$html->clear();
		unset($html);

		foreach($countrymap as $kk => $country)
		{
			// ========================
			// Lista POI dla każdego z krajów
			// ========================
			$buff = array();

			Logg("Pobieram listę POI dla: {$country['name']} [{$dat['host_url']}{$country['url']}]");
			$html = GetFile($dat['host_url'].$country['url']);

			if(!$html) {
				Logg("URL nieosiągalny! Kontynuuję...");
				continue;
			}

			$count = preg_match_all('#etrPark([^\[\s]+)\[(\d+)\]\s=\s"?([^;]+)"?;#m', $html, $matches);
			for($i=0; $i < $count; $i++) {
				$buff[$matches[2][$i]][$matches[1][$i]] = trim($matches[3][$i], '"\\ ');
			}

			Logg("Znalazłem ".count($buff)." nowych POI");

			foreach($buff as $k => $v) {
				$v['country'] = $country; // referencja kraju w każdym POI
				unset($v['country']['ids']); // inni krajanie dla tego POI ;) narazie niepotrzebne
				$v['wifi'] = $wifi?1:0;
				$v['Title_RAW'] = $v['Title'];
				$v['Title_FIX'] = FixTitle($v['Title_RAW'], $country['ccONZ']);
				$v['Title_ENC'] = myhtmlentities($v['Title_FIX']);
				$v['Title'] = "[{$v['Id']}".($wifi?',WiFi':'')."] {$v['Title_ENC']}, {$country['ccONZ']}";
				$v['url'] = 'dobry_parking,id,'.$v['Id'];

				// Oblicz extrema koordynatów <bounds>
				$v['fLat'] = round(floatval($v['Lat']) , $cfg['gpx_round']);
				$v['fLon'] = round(floatval($v['Lang']), $cfg['gpx_round']);

				$dat['gpx_minlat'] = min($dat['gpx_minlat'], $v['fLat']);
				$dat['gpx_minlon'] = min($dat['gpx_minlon'], $v['fLon']);
				$dat['gpx_maxlat'] = max($dat['gpx_maxlat'], $v['fLat']);
				$dat['gpx_maxlon'] = max($dat['gpx_maxlon'], $v['fLon']);

				// Oblicz precyzję koordynatów
				$tmp = explode('.', $v['Lat']);
				$dat['gpx_precision'] = max($dat['gpx_precision'], strlen($tmp[1]));
				$tmp = explode('.', $v['Lang']);
				$dat['gpx_precision'] = max($dat['gpx_precision'], strlen($tmp[1]));

				// Podlicz POIs
				if($wifi) {
					if($pois[$v['Id']])  $json['count_parking']--;
					$json['count_parking_wifi']++;
				}
				elseif(!$pois[$v['Id']]) $json['count_parking']++;

				$json['count_parking_all'] = $json['count_parking'] + $json['count_parking_wifi'];

				$pois[$v['Id']] = $v; // WiFi zastępuje Zwykły, jeśli był wcześniej...
				$countries[$kk]['ids'][] = $v['Id'];
			}
			$countries[$kk]['ids'] = array_unique($countries[$kk]['ids']);
			sort($countries[$kk]['ids'], SORT_NUMERIC);
		}
	}

	Logg("Konsoliduję znalezione kraje [{$json['count_countries']}] do [".count($countries)."]");
	$json['count_countries'] = count($countries);

	Logg("Sortuję kraje...");
	function sortCountries($a, $b) {
		return strcasecmp(utf2ascii($a['name']), utf2ascii($b['name']));
	}
	uasort($countries, 'sortCountries');

	Logg("Sortuję POI...");
	ksort($pois, SORT_NUMERIC);


	// ========================
	// Szczegóły dla każdego POI
	// ========================
	foreach($pois as $i => $poi)
	{
		$pois[$i]['info'] = array();
		$pois[$i]['opinie'] = array();

		Logg("Pobieram szczegóły dla: {$poi['Title_FIX']} [{$dat['host_url']}{$poi['url']}]");
		$html = GetFile($dat['host_url'].$poi['url'],1);

		if(!$html) {
			Logg("URL nieosiągalny! Kontynuuję...");
			continue;
		}

		foreach($html->find('#parking tr') as $tag) { // Dane parkingu. Uwaga! Text HTML
			$pois[$i]['info'][] = array(
				trim($tag->find('td.item',0)->plaintext),
				trim($tag->find('td.desc',0)->innertext),
			);
		}
		foreach($html->find('#opinie tr') as $tag) { // Komentarze
			$pois[$i]['opinie'][] = array(
				myhtmlentities($tag->find('td',0)->find('a',0)->plaintext),		// Nick
				myhtmlentities($tag->find('td',0)->find('span',0)->plaintext),	// Data
				myhtmlentities(FixComments($tag->find('td',1)->plaintext)),		// Text
			);
		}

		$html->clear();
		unset($html);
	}

	Logg("Zapisuję pobrane dane do [{$cfg['offline_file']}]", false);
	file_put_contents($cfg['offline_file'], serialize(array(
		'dat' => $dat,
		'json' => $json,
		'pois' => $pois,
		'countries' => $countries,
	)));

} /* END: [offline_mode == false] */


// ========================
// Google Maps
// ========================
Logg('');
if($cfg['map_download'])
{
	$json['map_count'] = 0; // Pobrane mapy
	$json['map_data'] = 0; // Rozmiar map
	$json['map_miss'] = 0; // Brakujące mapy
	$json['map_fail'] = 0; // Niedostępne mapy
	$json['map_orph'] = 0; // Obrazy map po usuniętych POI

	$map_dir_old = $cfg['map_dir'];

	if($cfg['map_delorphan']) {
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
		Logg('');
	}	
	
	if($cfg['map_redownload']) {
		Logg('Odświeżam pozostałe obrazy z Google Maps:');
		$cfg['map_dir'] = "{$cfg['map_dir']}redl/";
	}
	else Logg('Uzupełniam obrazy z Google Maps:');

	foreach($pois as $k => $poi)
		$json['map_miss'] += is_file($cfg['map_dir'].sprintf($cfg['map_file_mask'], $k, 'jpg')) ? 0 : 1;

	foreach($pois as $k => $poi)
	{
		$fpath = $map_dir_old   .sprintf($cfg['map_file_mask'], $k, 'jpg'); // mapa
		$fnull = $cfg['map_dir'].sprintf($cfg['map_file_mask'], $k, 'jpg'); // duch

		$fskip = !$cfg['map_redownload'] && is_file($fpath) && filesize($fpath) > $cfg['map_file_size'];
		$fskip |= $cfg['map_redownload'] && is_file($fnull);

		if($fskip) continue;

		if($map = GetStaticMap($poi)) {
			file_put_contents($fpath, $map); // mapa
			if($cfg['map_redownload'])
				file_put_contents($fnull, ''); // duch
			Logg('.',0,1,1);
		}
		else Logg("{$k}!",1,1,1);
	}
	Logg('');
	$cfg['map_dir'] = $map_dir_old;
}
else Logg('Pobieranie obrazów z Google Maps - wyłączone!');


// ========================
// Katalog roboczy
// ========================
Logg('');
rrmdir($cfg['zip_src_path']); // folder roboczy
rrmdir($cfg['zip_out_path']); // wyjściowy zip
mkdir($cfg['map_out_path'], 0777, true);
Logg("Tworzę katalog roboczy [".realpath($cfg['map_out_path'])."]",0);


// ========================
// Pliki [GPX] i inne
// ========================
Logg('');
Logg("Generuję plik [{$cfg['gpx_file']}.gpx]");
Logg("Precyzja koordynatów GPS: do {$dat['gpx_precision']} miejsc po przecinku.");

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<gpx
  version="1.0"
  creator="Notepad++, XAMPP, Apache, PHP '.phpversion().' (CLI), Brain"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns="http://www.topografix.com/GPX/1/0"
  xsi:schemaLocation="http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd">
<name>'.$cfg['gpx_file'].' ['.$dat['cdate'].']</name>
<desc>'.$cfg['subtitle'].'</desc>
<author>Orkan - orkans AT gmail DOT com</author>
<copyright>'.$cfg['title'].'</copyright>
<link>'.$dat['host_url'].'</link>
<time>'.$dat['date_atom'].'</time>
<bounds minlat="'.$dat['gpx_minlat'].'" minlon="'.$dat['gpx_minlon'].'" maxlat="'.$dat['gpx_maxlat'].'" maxlon="'.$dat['gpx_maxlon'].'"/>
';
foreach($pois as $poi) {
	$xml .= '<wpt lat="'.$poi['Lat'].'" lon="'.$poi['Lang'].'">'.PHP_EOL;
	$xml .= "  <name>{$dat['host_url']}{$poi['url']}</name>".PHP_EOL; // Nazwa dla google maps. TomTom wydaje się ignorować to pole, a google używa jako tytułu. UWAGA: google nie ładuje wszystkich :(
	$xml .= "  <desc>{$poi['Title']}</desc>".PHP_EOL; // Nazwa POI w Tomku (kolejność: <cmt>, <desc>, ...)
	$xml .= "  <cmt>{$poi['Title']}</cmt>".PHP_EOL; // Nazwa POI w Tomku (kolejność: <cmt>, <desc>, ...)
	$xml .= '</wpt>'.PHP_EOL;
}
$xml .= '</gpx>'.PHP_EOL;

if(strlen($xml) == file_put_contents("{$cfg['zip_src_path']}{$cfg['gpx_file']}.gpx", $xml))
{
	Logg("Konwertuję [GPX] -> TomTom POI file (.ov2) (tomtom)");
	exec("{$cfg['gpsbabel']} -i gpx -f {$cfg['zip_src_path']}{$cfg['gpx_file']}.gpx -o tomtom -F {$cfg['zip_src_path']}{$cfg['gpx_file']}.ov2");
//	Na google maps dziala [gpx]
//	Logg("Konwertuję [GPX] -> Google Earth (Keyhole) Markup Language (kml)");
//	exec("{$cfg['gpsbabel']} -i gpx -f {$cfg['zip_src_path']}{$cfg['gpx_file']}.gpx -o kml -F {$cfg['zip_src_path']}{$cfg['gpx_file']}.kml");
}
else Logg("Błędy w zapisie pliku [GPX]");


// ========================
// Wersja [HTM]
// ========================
Logg('');
Logg("Generuję pliki HTM:");
$poisJS = array();

$cfg['tpl_subpages']  = ceil($json['count_parking_all'] / $cfg['items_per_tpl']);
$cfg['tpl_items_last'] = $cfg['items_per_tpl'] - ($cfg['tpl_subpages'] * $cfg['items_per_tpl'] - $json['count_parking_all']);

$t = array(
	'index' => new XTemplate('inc/'.sprintf($cfg['tpl_file_mask'], 'index', '.tpl')),
	'subex' => new XTemplate('inc/'.sprintf($cfg['tpl_file_mask'], 'subex', '.tpl')),
	'mapex' => new XTemplate('inc/'.sprintf($cfg['tpl_file_mask'], 'mapex', '.tpl')),
	'cntry' => new XTemplate('inc/'.sprintf($cfg['tpl_file_mask'], 'cntry', '.tpl')),
);
foreach($t as $ttt) $ttt->assign(array(
	'TITLE'			=> $cfg['title'],
	'SUBTITLE'		=> $cfg['subtitle'],
	'POIS'			=> $json['count_parking_all'],
	'COUNTRIES'		=> $json['count_countries'],
	'SUBPAGES'		=> $cfg['tpl_subpages'],
	'BASE_INDEX'	=> '.',
	'BASE_SUBEX'	=> '..',
	'BASE_MAPEX'	=> '../..',
	'INDEX_FILE'	=> sprintf($cfg['tpl_file_mask'], 'index', ''),
	'SUBEX_FILE_JS'	=> sprintf($cfg['tpl_file_mask'], '"+ val +"', ''),
	'MAPEX_PATH'	=> $cfg['tpl_sub_dir'].$cfg['map_sub_dir'],
));


// ========================
// Podstrony POI
// ========================
Logg("podstrony",1,1);
for($i=1,$j=0,$pageID=0,reset($pois); list($k,$poi)=each($pois); $i++)
{
	if(!(($i-1)%$cfg['items_per_tpl'])) // Nowa strona?
	{
		$pageID = sprintf('%05d', ++$j);
		$pageURL = sprintf($cfg['tpl_file_mask'], $pageID, '');

		$itemsCount = ($j==$cfg['tpl_subpages']) ? $cfg['tpl_items_last'] : $cfg['items_per_tpl'];
		$itemsFrom	= (($j-1) * $cfg['items_per_tpl']) + 1;
		$itemsTo	= $itemsFrom + $itemsCount - 1;

		$t[$pageID] = clone $t['subex'];
		$t[$pageID]->assign(array(
			'POIS_FROM'	=> $itemsFrom,
			'POIS_TO'	=> $itemsTo,
			'SUBPAGE'	=> $j,
			'PAGEID'	=> $pageID,
		));
		Logg('|',0,1,0);
	}

	$pois[$k]['pid'] = $pageID;

	$poisJS[$k] = array(
		'txt'	=> $poi['Title'],
		'ccONZ' => $poi['country']['ccONZ'],
		'ccISO' => $poi['country']['ccISO'],
		'url'	=> sprintf($cfg['tpl_file_mask'], $pageID, '')."#item{$k}",
		'com'	=> count($poi['opinie']),
	);

	$map = array(
		'file'	=> sprintf($cfg['map_file_mask'], $k, 'htm'),
		'image'	=> sprintf($cfg['map_file_mask'], $k, 'jpg'),
	);


	$t[$pageID]->assign(array(
		'INDEX'			=> $i,
		'POI_ID'		=> $k,
		'POI_TITLE'		=> $poi['Title'],
		'POI_ONZ'		=> $poi['country']['ccONZ'],
		'POI_ISO'		=> $poi['country']['ccISO'],
		'POI_COM'		=> count($poi['opinie']),
		'POI_URL'		=> $dat['host_url'].$poi['url'],
		'POI_MAP_URL'	=> sprintf($cfg['map_url'], $poi['fLat'], $poi['fLon'], $poi['Title'], $poi['Title_ENC']),
		'POI_GEO_SZ'	=> $poi['fLat'],
		'POI_GEO_DL'	=> $poi['fLon'],
		'C_FILE'		=> sprintf($cfg['tpl_file_mask'], $poi['country']['ccISO'], ''),
		'M_FILE'	=> $map['file'], // [subex]:
		'M_IMAGE'	=> $map['image'],// xTemplate parsuje globalne w momencie tworzenia "new" objektu
	));
	$t[$pageID]->parse('MAIN.INDEX');

	// Główna zawartość...
	foreach($poi['info'] as $v) {
		if(!$v[0]) {
			$poi['info_nolabel'][] = $v[1];
			continue;
		}
		$t[$pageID]->assign(array(
			'INFO_LABEL'	=> $v[0],
			'INFO_CONTENT'	=> $v[1],
		));
		$t[$pageID]->parse('MAIN.PAGE.ROW');
	}

	// Główna zawartość bez tytułu...
	if(count($poi['info_nolabel']))
		$t[$pageID]->assign('POI_NOLABEL', implode(', ', $poi['info_nolabel']));


	// Komentarze...
	$poi['opinie'] = array_reverse($poi['opinie']);
	foreach($poi['opinie'] as $v) {
		$t[$pageID]->assign(array(
			'COMM_AUTOR'	=> $v[0],
			'COMM_TIME'		=> $v[1],
			'COMM_TEXT'		=> $v[2],
		));
		$t[$pageID]->parse('MAIN.PAGE.COMMENTS.ROW');
	}
	if(count($poi['opinie']))
		$t[$pageID]->parse('MAIN.PAGE.COMMENTS');

	// Mapy...
	if($cfg['map_append'])
	{
		if(@copy($cfg['map_dir'].$map['image'], $cfg['map_out_path'].$map['image']))
		{
			$tt = clone $t['mapex'];

			$tt->assign(array(
				'POI_TITLE'		=> $poi['Title'],
				'POI_ONZ'		=> $poi['country']['ccONZ'],
				'POI_ISO'		=> $poi['country']['ccISO'],
				'POI_GEO_SZ'	=> $poi['fLat'],
				'POI_GEO_DL'	=> $poi['fLon'],
				'C_FILE'		=> sprintf($cfg['tpl_file_mask'], $poi['country']['ccISO'], ''),
				'M_FILE'	=> $map['file'], // [subex]:
				'M_IMAGE'	=> $map['image'],// xTemplate parsuje globalne już w momencie tworzenia "new" objektu
			));
			$tt->parse('MAIN');
			$t[$pageID]->parse('MAIN.PAGE.SUBEX');

			// Zapis...
			file_put_contents($cfg['map_out_path'].$map['file'], $tt->text('MAIN'));
			unset($tt);
		} else {
			Logg("{$k}!",1,1,0);
		}
	}

	$t[$pageID]->parse('MAIN.PAGE');
	Logg('.',0,1,0);
}
unset($t['subex'], $t['mapex']);

foreach($t as $k => $v) { // musi być tutaj ze względu na ostatnią stronę!
	if(!preg_match("/^[0-9]+$/", $k)) continue;

	$t[$k]->parse('MAIN');
	file_put_contents($cfg['tpl_out_path'].sprintf($cfg['tpl_file_mask'], $k, ''), $t[$k]->text('MAIN'));
	unset($t[$k]);
}
Logg('');


// ========================
// Index i wg. krajów
// ========================
Logg("kraje",1,1);
foreach($countries as $k => $country)
{
	if(!count($country['ids'])) continue;

	$cID = $country['ccISO'];
	$tt = clone $t['cntry'];

	foreach(array($t['index'], $tt) as $ttt) {
		$ttt->assign(array(
			'C_NAME'	=> $country['name'],
			'C_ISO'		=> $country['ccISO'],
			'C_URL'		=> $country['url'],
			'C_POIS'	=> count($country['ids']),
			'POIS'		=> $json['count_parking_all'],
		));
	}
	$t['index']->parse('MAIN.COUNTRY');

	foreach($country['ids'] as $id) {
		$tt->assign(array(
			'POI_ID'	=> $id,
			'POI_TITLE'	=> $pois[$id]['Title'],
			'POI_ONZ'	=> $pois[$id]['country']['ccONZ'],
			'POI_ISO'	=> $pois[$id]['country']['ccISO'],
			'POI_COM'	=> count($pois[$id]['opinie']),
			'POI_FILE'	=> sprintf($cfg['tpl_file_mask'], $pois[$id]['pid'], ''),
		));
		$tt->parse('MAIN.POI');
	}

	// Zapis...
	$tt->parse('MAIN');
	file_put_contents($cfg['tpl_out_path'].sprintf($cfg['tpl_file_mask'], $cID, ''), $tt->text('MAIN'));
	Logg('.',0,1,0);
	unset($tt);
}
unset($t['cntry']);
Logg('');


// ========================
// Index
// ========================
Logg("index",1,1);
$t['index']->parse('MAIN');
file_put_contents($cfg['zip_src_path'].sprintf($cfg['tpl_file_mask'], 'index', ''), $t['index']->text('MAIN'));
Logg('.',0,1,0);
unset($t['index'], $t);
Logg(PHP_EOL);


// ========================
// Utwórz pois.js
// ========================
Logg("Zapisuję tablicę POI w formacie JSON");
file_put_contents('inc/sys/pois.js', 'orkan.subdir="'.$cfg['tpl_sub_dir'].'";orkan.pois='.json_encode($poisJS).';');


// ========================
// Kopiuj gotowce
// ========================
$i=0;
foreach(array(
	'ork_parking_MIX.bmp',
	'ork_parking_MIX.ogg',
	'sys',
) as $v) $i += rcopy("inc/$v", "{$cfg['zip_src_path']}$v");
Logg("Kopiuję $i dodatkowe pliki");


// ========================
// Sprzątanie i inne takie...
// ========================
Logg('');
Logg('Czas skryptu: '.FormatTime(get_execution_time()));
RenderSettings();
file_put_contents("{$cfg['zip_src_path']}{$cfg['gpx_file']}.log", $cfg['log']);

/*
 * Po dodaniu map przestał zapisywać ZIP-y
 * @TODO: Sprawdzić do jakiego rozmiaru można tworzyć pliki zip?
new ZipFolder($cfg['zip_out_path'], $cfg['zip_src_path']);
Logg("Utworzyłem archiwum ZIP [".realpath($cfg['zip_out_path'])."]",0);
*/

if(!$cfg['offline_mode'] && is_file($cfg['zip_out_path'])) {
	Logg("Usuwam katalog roboczy  [".realpath($cfg['zip_src_path'])."]",0);
	rrmdir($cfg['zip_src_path']);
}

Logg('Koniec!');
