<?php

/**
 * eTransport.pl Zakaz ruchu
 * http://etransport.pl/zakaz_ruchu
 *
 * Pobiera wszystkie strony ze szczegółami danych krajów
 * TODO: Zrobić aplikację offline z bazą danych
 */

error_reporting(E_ALL ^ E_NOTICE);
chdir(dirname(__FILE__)); // for Drag & Drop
require('../shared/php/functions.php');
require('../shared/php/simple_html_dom.php');
require('../shared/php/templates.php');
require('../shared/php/zip.php');

$cfg = array(
	'title'				=> 'eTransport',
	'subtitle'			=> 'Zakaz ruchu',
	'tmp_dir'			=> '../shared/tmp/',
	'log_file'			=> 'ork_zakazruchu',
	'htm_file_mask'		=> '%s_zakazruchu.htm',
	'zip_src_mask'		=> 'ork_zakazruchu_%s/',
	'zip_file_mask'		=> 'ork_zakazruchu.%s.zip',
	'count_interval'	=> 10, // Ilość zapytań w jednej serii
	'time_interval'		=> 2, // Odstęp (sek.) między każdą serią zapytań
	'logg_usleep'		=> 100000, // Odstęp (mikrosek.) między każdym logiem
	'log'				=> '', // Wygenerowany log
	'max_collstrlen'	=> 700, // max. il. znaków: Collapsible lub Listview
);

$dat = array( // dane w pliku offline
	'host_url'			=> 'http://etransport.pl/',
	'country_url'		=> 'zakaz_ruchu,belgia,20.html', // mniej najebane html'a tutaj
	'cdate'				=> date('YmdHis', time()),
	'year'				=> date('Y'),
	'date'				=> date('Y-m-d'),
	'date_long'			=> date('Y-m-d H:i:s'),
);

$json = array(
	'count_countries'		=> 0,
	'requests'				=> 0, // Ilość wykonanych zapytań
	'req_data'				=> 0, // Ilość pobranych danych
);

$json2cli = array(
	'count_countries'		=> 'Kraje',
	'requests'				=> 'Wysłane zapytania',
	'req_data'				=> 'Pobrane dane',
);


// ========================
// START :: Here !!!
// ========================
Logg("Witaj na {$cfg['title']}, {$cfg['subtitle']}!");
Logg('Aktualny czas serwera: '.date('Y-m-d H:i:s'));
Logg('');

// ========================
// Te dane $cfg trzeba zmixować z $dat niestety
$cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
$cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);

// ========================
// Lista krajów
// ========================
Logg("Pobieram listę krajów: [{$dat['host_url']}{$dat['country_url']}]");
if(!$html = GetFile($dat['host_url'].$dat['country_url'],1)) {
	Logg("URL nieosiągalny! Przerywam...");
	exit;
}
Logg('');

rrmdir($cfg['zip_src_path']); // folder roboczy
rrmdir($cfg['zip_out_path']); // wyjściowy zip
mkdir($cfg['zip_src_path'], 0777, true);
$t = new XTemplate('inc/ork_zakazruchu.tpl.htm');

$i=0;
foreach($html->find('#PRAWA div.sidetable table tr') as $tr)
{
	if($i) // omiń pierwszy wiersz
	{
		$a = $tr->find('a',0);
		$country = array();
		$country['name']	= $a->plaintext;
		$country['ccONZ']	= GetCountryONZ($country['name']);
		$country['ccISO']	= $ccONZ2ISO[$country['ccONZ']][0];
		$country['url']		= $a->href;

		// ========================
		// Pobierz dane kraju
		// ========================
		Logg("Pobieram: {$country['name']} [{$country['url']}]");
		if(!$html2 = GetFile($dat['host_url'].$country['url'],1)) {
			Logg("URL nieosiągalny! Kończę...");
			exit;
		}

		$json['count_countries']++;
		$t2 = clone $t;

		$t2->assign(array(
			'TITLE'		=> $cfg['title'],
			'SUBTITLE'	=> $cfg['subtitle'],
			'BASE_INDEX'=> '.',
			'C_NAME'	=> $country['name'],
			'C_ONZ'		=> $country['ccONZ'],
			'C_ISO'		=> $country['ccISO'],
			'C_URL'		=> $dat['host_url'].$country['url'],
		));

		// ========================
		// Pobierz tabele informacji
		// ========================
		$j=0;
		$parseListview = false;
		foreach($html2->find('#inf tr') as $tr2)
		{
			if($j) // omiń pierwszy wiersz
			{
				$info1 = $tr2->find('td',0)->plaintext; // tytuł
				$info2 = $tr2->find('td',1)->innertext; // zawartość
				if(!strlen($info2)) continue;
				$info2 = strip_tags($info2, '<a><b><br><p><ul><ol><li>'); // Popraw błędy w html'u

				$t2->assign(array(
					'INFO_ID'		=> $j,
					'INFO_LABEL'	=> $info1, // tytuł,
					'INFO_CONTENT'	=> $info2, // zawartość
				));

				if(strlen($info2) > $cfg['max_collstrlen']) {
					$t2->parse('MAIN.LISTVIEW.ROW');
					$t2->parse('MAIN.PAGE');
					$parseListview = true;
				} else {
					$t2->parse('MAIN.COLLAPSIBLE');
				}
			}
			$j++;
		}

		// ========================
		// "Najbliższe dni zakazów"
		// ========================
		$info1 = "Najbliższe dni zakazów"; // tytuł
		$info2 = $html2->find('#zakazy',0)->outertext; // Pobierz tabele
		if(strlen($info2)) {
			$info2 = FixZakazyTable($info2);
			$t2->assign(array(
				'INFO_ID'		=> ++$j,
				'INFO_LABEL'	=> $info1, // tytuł,
				'INFO_CONTENT'	=> $info2, // zawartość
			));
			$t2->parse('MAIN.LISTVIEW.ROW');
			$t2->parse('MAIN.PAGE');
			$parseListview = true;
		}

		// ========================
		// Zapisz...
		// ========================
		if($parseListview)
			$t2->parse('MAIN.LISTVIEW');
		$t2->parse('MAIN');

		$k = explode(',', $country['url']);
		file_put_contents($cfg['zip_src_path'].sprintf($cfg['htm_file_mask'], $k[1]), $t2->text('MAIN'));

		$html2->clear();
		unset($html2, $t2);
	}
	$i++;
}
$html->clear();
unset($html, $t);
Logg('');

// ========================
// Kopiuj gotowce
// ========================
$i=0;
foreach(array(
	'sys',
) as $v) $i += rcopy("inc/$v", "{$cfg['zip_src_path']}$v");
Logg("Kopiuję $i dodatkowe pliki");


// ========================
// Sprzątanie i inne takie...
// ========================
Logg('');
Logg('Czas skryptu: '.FormatTime(get_execution_time()));
RenderSettings();
file_put_contents("{$cfg['zip_src_path']}{$cfg['log_file']}.log", $cfg['log']);

new ZipFolder($cfg['zip_out_path'], $cfg['zip_src_path']);

Logg("Utworzyłem archiwum ZIP [".realpath($cfg['zip_out_path'])."]");
Logg("Usuwam katalog roboczy  [".realpath($cfg['zip_src_path'])."]");
rrmdir(substr($cfg['zip_src_path'], 0, -1));

Logg('Koniec!');
