<?php

/**
 * Podróże kulinarne śladami Roberta Makłowicza - nieoficjalna strona programu
 * http://kuchnia.mckornik.com
 *
 * Ściąga bazę przepisów ze strony
 * Przepisy podzielone są na dwie serie:  {nr} - stara, #{nr} - nowa
 * Ja zachowuje po kolei, bez rozdzielania. Druga seria zaczyna sie od id:[233]
 */

error_reporting(E_ALL ^ E_NOTICE);
chdir(dirname(__FILE__)); // for Drag & Drop
require('../shared/php/functions.php');
require('../shared/php/simple_html_dom.php');
require('../shared/php/templates.php');
require('../shared/php/zip.php');
define('SED_CODE', TRUE);
require('../shared/php/database.mysql.php');

$cfg = array(
	'title'				=> 'Robert Makłowicz w podróży',
	'subtitle'			=> 'Przepisy i podróże kulinarne',
	'tmp_dir'			=> '../shared/tmp/',
	'log_file'			=> 'ork_mckornik',
	'out_file_mask'		=> '%1$s_%2$s.txt',
	'zip_src_mask'		=> 'ork_mckornik_%s/',
	'zip_file_mask'		=> 'ork_mckornik.%s.zip',
	'count_interval'	=> 100000000000000, // Ilość zapytań w jednej serii
	'time_interval'		=> 1, // Odstęp (sek.) między każdą serią zapytań
	'logg_usleep'		=> 100000, // Odstęp (mikrosek.) między każdym logiem
	'log'				=> '', // Wygenerowany log
	'max_recipes'		=> 6, // max. il. przepisów do odszukania w danym odcinku
	'mysqlhost' 		=> 'localhost',
	'mysqluser' 		=> 'root',
	'mysqlpassword' 	=> '',
	'mysqldb' 			=> 'mckornik',
);

$dat = array( // dane w pliku offline
	'host_url'			=> 'http://kuchnia.mckornik.com/',
	'start_url'			=> 'start.php',
	'journey_url_mask'	=> 'start.php?lp_wedrowki=%1$d',
	'recipe_url_mask'	=> 'start.php?lp_wedrowki=%1$d&lp_przepisu=%2$d',
	'sezons'			=> array(1 => 222, 2 => 51),
	'sezon_mask'		=> 'S%1$02dE%2$03d',
	'cdate'				=> date('YmdHis', time()),
	'year'				=> date('Y'),
	'date'				=> date('Y-m-d'),
	'date_long'			=> date('Y-m-d H:i:s'),
);

$json = array(
	'episodes'			=> 0,
	'recipes'			=> 0,
	'requests'			=> 0, // Ilość wykonanych zapytań
	'req_data'			=> 0, // Ilość pobranych danych
);

$json2cli = array(
	'episodes'			=> 'Odcinki',
	'recipes'			=> 'Przepisy',
	'requests'			=> 'Wysłane zapytania',
	'req_data'			=> 'Pobrane dane',
);

// ========================
// START :: Here !!!
// ========================
Logg("{$cfg['title']} - {$cfg['subtitle']}");
Logg('Aktualny czas serwera: '.date('Y-m-d H:i:s'));
Logg('');

sed_sql_connect($cfg['mysqlhost'], $cfg['mysqluser'], $cfg['mysqlpassword'], $cfg['mysqldb']);

// ========================
// Te dane $cfg trzeba zmixować z $dat niestety
$cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
$cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);

// ========================
// Lista podstron
// ========================
Logg("Pobieram spis treści - {$dat['host_url']}{$dat['start_url']}");
if(!$html = GetFile($dat['host_url'].$dat['start_url'],1)) {
	Logg("URL nieosiągalny! Przerywam...");
	exit;
}

$ids = array();
foreach($html->find('table',2)->find('table tr') as $tr)
{
	// Ze spisu treści przechwyć tylko główne linki do przepisów:
	// - pierwsza komórka zawiera tylko {nr} lub #{nr}
	$str = str_replace(array("\t",'#',' ','&nbsp;'), '', $tr->find('td',0)->plaintext);
	if(!is_numeric($str)) continue;
	
	$ids[] = $tr->find('a',0)->name;
	$json['episodes']++;
}

if(!$json['episodes']) {
	Logg("Brak odcinków! Kończę...");
	exit;
}

sort($ids, SORT_NUMERIC);
Logg("Znalazłem {$json['episodes']} odcinków");


rrmdir($cfg['zip_src_path']); // folder roboczy
rrmdir($cfg['zip_out_path']); // wyjściowy zip
mkdir($cfg['zip_src_path'], 0777, true);
$t = new XTemplate('inc/podroz.tpl.txt');
$html->clear();
unset($html);


// ========================
// Odcinki
// ========================
//$ids = array(272);
foreach($ids as $kid => $id)
{
	$info = array();
	$info['journey_url'] = sprintf($dat['journey_url_mask'], $id);
	
	if(!$html = GetFile($dat['host_url'].$info['journey_url'],1)) {
		Logg("[{$dat['host_url']}{$info['journey_url']}] nieosiągalny! Kończę...");
		exit;
	}
	
	$matches = array();
	$info['JOURNEY_TITLE'] = my_html_entity_decode($html->find('title',0)->plaintext);
	preg_match('/nr (#?)([0-9]+) /', $info['JOURNEY_TITLE'], $matches);
	$info['sezon'] = $matches[1] ? 2 : 1;
	$info['epizode'] = (int)$matches[2];
	$info['JOURNEY_LABEL'] = sprintf($dat['sezon_mask'], $info['sezon'], $info['epizode']);
	//$info['JOURNEY_TITLE'] = preg_replace('/nr #?[0-9]+/', $info['sezon'], $info['JOURNEY_TITLE']);
		
	$info['JOURNEY_TITLE'] = explode(' - ', $info['JOURNEY_TITLE']);
	$info['JOURNEY_TITLE'] = $info['JOURNEY_TITLE'][1];
	
	$info['JOURNEY_DESC']	= my_html_entity_decode($html->find('table',2)->children(0)->children(1)->children(0)->children(0)->children(0)->find('tr',1)->plaintext);
	$info['JOURNEY_DESC']	= FixComments($info['JOURNEY_DESC']);
	
	Logg('');
	Logg("{$info['JOURNEY_LABEL']} - {$info['JOURNEY_TITLE']}");
	
	$t2 = clone $t;

	$t2->assign(array(
		'TITLE'	=> $cfg['title'],
		'JOURNEY_LABEL'	=> $info['JOURNEY_LABEL'],
		'JOURNEY_TITLE'	=> $info['JOURNEY_TITLE'],
		'JOURNEY_DESC'	=> $info['JOURNEY_DESC'],
	));
	
	
	// ========================
	// DB::zapis JOURNEY
	// ========================
	$sql = sed_sql_query("INSERT INTO journeys (
	sezon, 
	epizode, 
	title, 
	description, 
	uri) 
	VALUES (
	{$info['sezon']}, 
	{$info['epizode']}, 
	'".sed_sql_prep($info['JOURNEY_TITLE'])."',
	'".sed_sql_prep($info['JOURNEY_DESC'])."',
	'".sed_sql_prep($info['journey_url'])."')
	ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),
	title='".sed_sql_prep($info['JOURNEY_TITLE'])."',
	description='".sed_sql_prep($info['JOURNEY_DESC'])."',
	uri='".sed_sql_prep($info['journey_url'])."'");

	$info['journey_id'] = mysql_insert_id();
	$sql = sed_sql_query("DELETE FROM recipes WHERE journey={$info['journey_id']}");
	
	
	// ========================
	// Przepisy z odcinka
	// ========================
	for($r=1; $r<=$cfg['max_recipes']; $r++)
	{
		Logg("Szukam przepisu #$r...",1,1);
		
		$info['recipe'] = array();
		$info['recipe']['recipe_url'] = sprintf($dat['recipe_url_mask'], $id, $r);
		
		if(!$html2 = GetFile($dat['host_url'].$info['recipe']['recipe_url'],1)) {
			Logg("[{$dat['host_url']}{$info['recipe']['recipe_url']}] nieosiągalny! Przerywam...");
			break;
		}
				
		// ========================
		// Znajdz poszczególne wpisy
		// ========================
		$i=0;
		foreach(array(
			'RECIPE_CATEGORY'		=> 'Kategoria / category:',
			'RECIPE_INGREDIENTS'	=> 'Składniki / meal compositions:',
			'RECIPE_DIRECTIONS'		=> 'Plan / recipe:',
		) as $k => $v)
		{
			for(;$i<10;$i++)
			{
				$s = $html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',$i)->plaintext;
				$s = trim($s);
				if($s == $v) {
					$s = my_html_entity_decode($html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',++$i)->plaintext);
					$info['recipe'][$k] = $s;
					break;
				}
			}
		}

		if(mb_strlen($info['recipe']['RECIPE_DIRECTIONS']) < 4) {
			Logg('brak');
			continue; // Nie przerywaj, czasami numeracja nie ma ciągłości!
		}
		
		$json['recipes']++;
		$info['recipe']['RECIPE_TITLE'] = my_html_entity_decode($html2->find('title',0)->plaintext);
		$info['recipe']['RECIPE_TITLE'] = explode(' :: ', $info['recipe']['RECIPE_TITLE']);
		$info['recipe']['RECIPE_TITLE'] = $info['recipe']['RECIPE_TITLE'][1];
		$info['recipe']['RECIPE_CATEGORY']		= FixCategory($info['recipe']['RECIPE_CATEGORY']);
		$info['recipe']['RECIPE_DIRECTIONS']	= FixComments($info['recipe']['RECIPE_DIRECTIONS']);
		
		foreach(explode("\n", $info['recipe']['RECIPE_INGREDIENTS']) as $v) {
			$v = trim($v);
			if(!empty($v)) {
				$v = FixComments($v);
				$info['recipe']['ingredients'][] = $v;
				$t2->assign('RECIPE_INGREDIENT', $v);
				$t2->parse('MAIN.RECIPE.INGREDIENTS');
			}
		}
		
		$t2->assign(array(
			'RECIPE_TITLE'		=> $info['recipe']['RECIPE_TITLE'],
			'RECIPE_CATEGORY'	=> $info['recipe']['RECIPE_CATEGORY'],
			'RECIPE_DIRECTIONS'	=> $info['recipe']['RECIPE_DIRECTIONS'],
		));
		$t2->parse('MAIN.RECIPE');
		
		
		// ========================
		// DB::dodaj RECIPE
		// ========================
		$sql = sed_sql_query("INSERT INTO recipes (
		journey,
		title,
		category,
		ingredients,
		directions,
		uri)
		VALUES (
		{$info['journey_id']}, 
		'".sed_sql_prep($info['recipe']['RECIPE_TITLE'])."',
		'".sed_sql_prep($info['recipe']['RECIPE_CATEGORY'])."',
		'".sed_sql_prep(serialize($info['recipe']['ingredients']))."',
		'".sed_sql_prep($info['recipe']['RECIPE_DIRECTIONS'])."',
		'".sed_sql_prep($info['recipe']['recipe_url'])."')");

		Logg(my_html_entity_decode($info['recipe']['RECIPE_TITLE']));
	}
	
	
	// ========================
	// Zapisz...
	// ========================
	$t2->parse('MAIN');
	file_put_contents($cfg['zip_src_path'].sprintf($cfg['out_file_mask'], $info['JOURNEY_LABEL'], FixFilename($info['JOURNEY_TITLE'])), $t2->text('MAIN'));
	
	$html2->clear();
	unset($html2, $t2);
	
//if($kid > 2) break;
}

$html->clear();
unset($html, $t);


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
