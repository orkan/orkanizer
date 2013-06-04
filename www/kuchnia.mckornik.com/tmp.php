<?php

if(1){
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
}

if(0){
$s = 'Podróże Makłowicza nr 001 - Smak wawelski';
$s = 'Podróże Makłowicza nr #040 - Islandia, „…i Polacy”';
	$k = 0;
	$fname = explode(' - ', $s);
	$fname = FixFilename($fname[1]);
	
	Logg($fname);
	Logg(sprintf('%1$03d_%2$s.txt', $k+1, $fname));
	
	//file_put_contents(sprintf('%1$03d_%2s.txt', $k+1, $fname), "blah");
}

if(0){
$id = 4;
	// ========================
	// Przepisy z odcinka
	// ========================
	for($r=1;;$r++)
	{
		Logg("Pobieram przepis #$r");
		
		$info['recipe_url'] = sprintf($dat['recipe_url_mask'], $id, $r);
Logg("URL: ".$dat['host_url'].$info['recipe_url']);
		if(!$html2 = GetFile($dat['host_url'].$info['recipe_url'],1)) {
			Logg("URL [{$info['recipe_url']}] nieosiągalny! Przerywam...");
			break;
		}
		
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
					$s = $html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',++$i)->plaintext;
					$s = trim($s);
					$info[$k] = $s;
					break;
				}
			}
		}
		
if($writetofile['fp'] = fopen('tmp.txt', 'w')) { fwrite($writetofile['fp'], print_r($info,1)); fclose($writetofile['fp']); }
exit;
		
		$info['RECIPE_TITLE']		= $html2->find('title',0)->plaintext;
		$info['RECIPE_CATEGORY']	= $html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',3)->plaintext;
		$info['RECIPE_INGREDIENTS']	= $html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',5)->plaintext;
		$info['RECIPE_DIRECTIONS']	= $html2->find('table',2)->children(0)->children(1)->children(0)->find('tr',7)->plaintext;
		
		if(empty($info['RECIPE_DIRECTIONS']))
			break;
		
		$json['recipes']++;
		
		foreach(explode("\n", $info['RECIPE_INGREDIENTS']) as $v) {
			$v = trim($v);
			if(!empty($v)) {
				Logg("\$v: $v");
				//$t2->assign('RECIPE_INGREDIENT', $v);
				//$t2->parse('MAIN.RECIPE.INGREDIENTS');
			}
		}
/*
		$t2->assign(array(
			'RECIPE_TITLE'		=> $info['RECIPE_TITLE'],
			'RECIPE_CATEGORY'	=> FixCategory($info['RECIPE_CATEGORY']),
			'RECIPE_DIRECTIONS'	=> FixComments($info['RECIPE_DIRECTIONS']),
		));
		$t2->parse('MAIN.RECIPE');
*/
		
	}
}

if(0){
$a1 = array(
	'Podróże Makłowicza nr 001 - Smak wawelski',
	'Podróże Makłowicza nr 104 - Smak Montevideo',
	'Podróże Makłowicza nr #012 - Ekwador, Pod wulkanem',
	'Podróże Makłowicza nr #049 - Armenia, Północ',
);

$out = array();
// Formatuj nr odcinka
foreach($a1 as $v) {
	preg_match('/nr (#?)([0-9]+) /', $v, $matches);
	$s = sprintf($dat['sezon_mask'], $matches[1] ? 2 : 1, (int)$matches[2]);
	$v2 = preg_replace('/nr #?[0-9]+/', $s, $v);
	
	$out[] = array($v,$s, $v2);
}
if($writetofile['fp'] = fopen('tmp.txt', 'w')) { fwrite($writetofile['fp'], print_r($out,1)); fclose($writetofile['fp']); }

}

if(0){
$s = 'Dzień dobry państwu. Wbrew pozorom nie jest t reemejk filmu &#8222;janosik&#8221; &#8211; &#8222;janosik w 20 lat później&#8221;. Jesteśmy w 80 dań dookoła świata w Zakopanym, po to by wyjaśnić państwu, co to znaczy &#8221;dolina pięciu smaków&#8221;.';

//$s = myhtmlentities($s);
$s = html_entity_decode($s, ENT_NOQUOTES, 'UTF-8');

if($writetofile['fp'] = fopen('tmp.txt', 'w')) { fwrite($writetofile['fp'], $s); fclose($writetofile['fp']); }

}

if(1){
$desc = 'Dzień dobry państwu. Wbrew pozorom nie jest t reemejk filmu „janosik” – „janosik w 20 lat później”. Jesteśmy w 80 dań dookoła świata w Zakopanym, po to by wyjaśnić państwu, co to znaczy ”dolina pięciu smaków”. Pokażemy absolutne delicje miejscowej kuchni. Będzie pysznie gwarantuję. Nie wypada żeby ceper chodził zbyt długo w góralskim stroju, więc ja jestem już po cywilnemu. A jestem proszę państwa w Kuźnicach. Tutaj zaraz jest bacówka. A w tej bacówce zachodzi bardzo ważny proces robienia serów. Góralskie sery: bundz i oscypek to jedyne polskie sery godne naprawdę światowej kariery natychmiast. Wiemy jak one wyglądają, ale nie wiemy dokładnie jak się je robi. Zaraz będziemy chcieli to państwu pokazać Oscypek to tradycyjny polski ser produkowany na Podhalu. Charakteryzuje się określonym smakiem, konsystencją i barwą. Oryginalny oscypek wytwarzany jest z owczego mleka według tradycyjnej góralskiej metody. Ugniatany w stosownej temperaturze, moczony w solance i odpowiednio przechowywany, ma charakterystyczny słony smak, lekko kremową barwę i zawartość tłuszczu nie mniejszą niż 60 proc. Swoje walory zawdzięcza przede wszystkim pochodzeniu z terenów górskich, gdzie czynniki naturalne oraz produkcja według regionalnej metody, mają decydujący wpływ na jakość i cechy charakterystyczne produktu. Jesteśmy w zadymionej bacówce. Półmrok. Z boku pali się ognisko. Trzech mężczyzn pracuje. To miejsce wygląda magicznie, jak alchemiczna jakaś kuchnia. Zaraz zobaczymy, co tu się dzieje.. Panowie wiem tylko, że jest mleko, mleko owcze, co się dzieje dalej? Jak wygląda to od początku? Powstawanie oscypka rozpoczyna się w momencie dojenia owiec na hali, gdzie juhasi trzy razy dziennie zaganiają owce do koszar. Następnie mleko przecedzane jest przez lniane płótno (żeby wyłapać wszystkie paprochy, muchy, błoto) do, puciery, czyli dużej, przesadzistej beczki. W dużej kadzi jest świeże mleko. Ważne jest by miało ono naturalną temperaturę 36-40 oC. Ani więcej, ani mniej.Teraz baca wsypuje do mleka podpuszczkę składająca się z enzymów trawiennych wydzielanych przez śluzówkę żołądka cieląt. Dodanie enzymów powoduje ścinanie się białka w mleku na galaretę, i tak po półgodzinnym mieszaniu powstaje zbita masa serowa, grudki białego sera. ("Temperatura musi być taka, żeby ręce można włożyć, ale się nie poparzyć"), Masę tą juhas rozbija drewnianym kijem, zwyczajowo zwanym ferulą na drobne kawałki i parzy wodą rozgrzaną do temperatury 60-70oC, najlepiej czerpaną prosto z potoku. Ser osadza się na dnie i juhas zbija go rękami w jednolita bryłę. - To jest też wstępna pasteryzacja. Aby wszystkie miały jednakową wielkość, jednakową wagę, mniej więcej jednakowo wyglądały Po pół godzince felurą roztrzepuję zsiadłe mleko i nabijam równe porcje do kubełka [miarki]. Baca drewnianym czerpakiem nabiera około litr sera i wyciska z niego serwatkę, kilka razy zanurzając ser w gorącej wodzie. I tutaj jest kolejny etap jakby produkcji prawda Robi z nich kule i podaje juhasowi. Ten zręcznie formuje owalne oscypki ("Gniecie się je do miękkości, żeby były gibkie jak plastelina"). Wstępnie uformowane wrzuca do kociołka zawieszonego nad ogniskiem. Grudkę odciśnięto sera umieszcza w drewniej foremce - oscypiorce, która nadaje mu charakterystyczny wrzecionowaty kształt ze zdobionym walcem po środku. Żeby ze środka wycisnąć wodę i powietrze, aby pozostał sam tłuszcz i białko, ser przebija drutem. Tak uformowany ostatecznie oscypek kierowany jest do kąpieli solankowej "rosołu na jedną dobę. Soli w wodzie jest tak dużo, że ser nie tonie. Sól niszczy zarazki oraz wyciąga wodę z sera. Następnego dnia nasolone po kąpieli sery wędrują pod powałę gdzie będą leżakować około tygodnia zanim powędruje do wędzarni. Tam przez cztery do siedmiu dni omiatane dymem z ogniska, powoli się wędzą. Widzą państwo ile to trzeba trudu i jak to skomplikowany niezwykle proces, żeby na nasze stoły trafił ten ser. Ale to jest ser, który się pamięta do końca życia. Kto go raz spróbuje będzie go jadł zawsze. Górale są tradycyjnie gościnni. Chętnie częstują żyntycą (podgrzewane mlekiem, a dokładnie serwatka pozostała z produkcji oscypków). Kroją też bundz, czyli biały owczy ser. Próbujemy. Prawdziwy oscypek na strychu może leżeć przez całą zimę. Nie straszne mu ani słońce, ani mróz. W Zakopanem do stołu lepiej niż gong przywołuje nas dźwięk trąbity. Przed chwilą rozmawialiśmy o oscypkach, ja je nawet próbowałem. Były pyszne. Ale jedynie podnieciły mój wielki apetyt. Więc teraz coś konkretnego, typowo regionalnego, cos typowo zakopiańskiego. A więc kwaśnicy. Kwaśnica to jest zupa, jak sama nazwa wskazuje kwaśna :-))';
$url = 'http://kuchnia.mckornik.com/start.php?lp_wedrowki=3&lp_przepisu=1';

sed_sql_connect($cfg['mysqlhost'], $cfg['mysqluser'], $cfg['mysqlpassword'], $cfg['mysqldb']);
unset($cfg['mysqlhost'], $cfg['mysqluser'], $cfg['mysqlpassword']);

$sql = sed_sql_query("INSERT INTO journeys (
sezon, 
epizode, 
title, 
description, 
uri) 
VALUES (
1, 
32, 
'".sed_sql_prep('Smak wawelski')."',
'".sed_sql_prep($desc)."',
'".sed_sql_prep($url)."')");


}














