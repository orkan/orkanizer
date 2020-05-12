<?php
// ========================
// Obsluga bledow
function ShowErrors($s) {
    global $t, $errors, $log;
    if(!empty($s)) $errors[] = $s;
    if(count($errors)) {
        $t->assign('ERRORS', implode('<br>', $errors));
        $t->parse('MAIN.ERROR');
        if(count($log)) {
            $t->assign('LOG', implode("\n", $log));
            $t->parse('MAIN.DONE');
        }
        $t->parse('MAIN');
        $t->out('MAIN');
        exit;
    }
}
// ========================
// Szuka sciezek w podanym pliku
// Zwraca bezwzgledne sciezki w tablicy.
// Wywoluje filesize() na kazdym wpisie, ale nie ma problemu, bo PHP keszuje wyniki.
// [$all]: true - zapisuje nawet nieodnalezione pozycje
function ImportPlaylist($path, $all=false) {
    $out = array();
    if(empty($path)) ShowErrors('Podaj ścieżkę do playlist!');

    $buff = file_get_contents($path);
    if($buff !== false)
    {
        $buff = str_replace("\r", '', $buff);
        $buff = explode("\n", $buff);
        $buff[0] = ltrim($buff[0], "\xef\xbb\xbf"); // UTF-8 BOM !!!

        foreach($buff as $v)
        {
            $v = trim($v);
            if(empty($v)) continue;
            if($v[0] == '#') continue;

            if($all) 
            {
                $out[] = $v;
            }
            else
            {
                if($rpath = realpath($v))
                    $out[] = $rpath;
            }
        }
        return $out;
        
    }
    else ShowErrors("Nie mogę odnalezć pliku: $path");
}
// ========================
// Zapis playlisty z tablicy
// Dodaje komentarz w pierwszej lini (iTunes pomija pierwsza linie!)
function ExportPlaylist($path, $arr) {
    global $cfg;
    file_put_contents($path, $cfg['log_intro'].implode("\n", $arr));
}

function get_execution_time()
{	// PHP.NET langpavel at phpskelet dot org 19-May-2011 08:46
    static $microtime_start = null;
    if($microtime_start === null) {
        $microtime_start = microtime(true);
        return 0.0;
    }
    return microtime(true) - $microtime_start;
}
get_execution_time();

function Logg($s, $save=true, $inline=false, $sleep=true) {
	global $json, $cfg;
	if($save) $cfg['log'] .= $s. PHP_EOL;
	echo utf2dos($s);
	if(!$inline) echo PHP_EOL;
	if($sleep) usleep($cfg['logg_usleep']);
}

function GetFile($url, $dom=false) {
	global $json, $cfg;
	if(!(++$json['requests']%$cfg['count_interval'])) {
		RenderSettings();
		Logg("Czekam {$cfg['time_interval']} sek...");
		sleep($cfg['time_interval']);
	}
	$out = @file_get_contents($url);
	$json['req_data'] += strlen($out);
	$out = iso2utf($out);
	if($dom) $out = str_get_html($out);
	return $out;
}

function GetImage($url) {
	global $json, $cfg;
	if(!(++$json['requests']%$cfg['count_interval'])) {
		RenderSettings();
		Logg("Czekam {$cfg['time_interval']} sek...");
		sleep($cfg['time_interval']);
	}
	return @file_get_contents($url);
}

function GetStaticMap($poi) {
	global $json, $cfg;
	usleep($cfg['logg_usleep']);
	$url = sprintf($cfg['map_url_static'], $poi['fLat'], $poi['fLon'], $cfg['map_zoom']);
	$file = GetImage($url);
	$size = strlen($file);
	if($size) {
		$json['map_count']++;
		$json['map_data'] += $size;
		$json['map_miss']--;
		if($size < $cfg['map_file_size']) {
			$json['map_fail']++;
			$json['map_miss']++;
			$file = null;
		}
	}
	return $file;
}

function RenderSettings() {
	global $json, $json2cli;
	$len = 0; $out = PHP_EOL;
	foreach($json2cli as $k => $v) {$vlen = mb_strlen($v,'utf-8'); $len = $len > $vlen ? $len: $vlen;}
	foreach($json2cli as $k => $v) {
		$n = Nbr($json[$k], in_array($k, array('req_data','map_data')));
		$out .= "[$v]".str_repeat('.',$len+2-mb_strlen($v,'utf-8')).": $n".PHP_EOL;
	}
	Logg($out);
}

function Nbr($number, $bytes=false) {
	return ($bytes) ? FormatBytes($number) : number_format($number, 0, ',', ' ');
}

function FormatBytes($bytes=0) {
	//eric heudiard 11-Aug-2010 02:37 http://www.php.net/manual/en/function.filesize.php#99333
	$sizes = array('bajtów','KB','MB','GB','TB','PB','EB','ZB','YB');
	return $bytes ? (round($bytes/pow(1024, ($i = floor(log($bytes, 1024)))), $i > 1 ? 2 : 0)." {$sizes[$i]}") : 'n/a';
}

function FormatTime($t) {
	$d = $h = $m = 0;
	$s = explode('.', $t);
	$s = $s[0];
	
    if($s>=86400) {
		$d = floor($s/86400);
		$s = floor($s%86400);
	}
	if($s>=3600) {
		$h = floor($s/3600);
		$s = floor($s%3600);
	}
	if($s>=60) {
		$m = floor($s/60);
		$s = floor($s%60);
	}
	//return ($h?sprintf('%02d',$h).' godz. ':'').($m?sprintf('%02d',$m).' min. ':'').($s?sprintf('%02d',$s).' sek.':'');
	//return ($h?"$h godz. ":'').($m?"$m min. ":'').($s?"$s sek.":'');
	return ($d?"{$d}d ":'').($h?"{$h}g ":'').($m?"{$m}m ":'').($s?"{$s}s":'');
};

$ccONZ = array( // 41 kraje
	'AL'	=> array('Albania','Albania',),
	'A'	=> array('Austria','Austria'),
	'B'	=> array('Belgia','Belgia'),
	'BY'	=> array('Białoruś','Bialorus'),
	'BIH'	=> array('Bośnia i Hercegowina','Bosnia i Hercegowina'),
	'BG'	=> array('Bułgaria','Bulgaria'),
	'HR'	=> array('Chorwacja','Chorwacja'),
	'MNE'	=> array('Czarnogóra','Czarnogora'),
	'CZ'	=> array('Czechy','Czechy'),
	'DK'	=> array('Dania','Dania'),
	'EST'	=> array('Estonia','Estonia'),
	'RU'	=> array('Federacja Rosyjska','Federacja Rosyjska'),
	'FIN'	=> array('Finlandia','Finlandia'),
	'F'	=> array('Francja','Francja'),
	'GR'	=> array('Grecja','Grecja'),
	'E'	=> array('Hiszpania','Hiszpania'),
	'NL'	=> array('Holandia','Holandia'),
	'IRL'	=> array('Irlandia','Irlandia'),
	'KZ'	=> array('Kazachstan','Kazachstan'),
	'FL'	=> array('Liechtenstein','Liechtenstein'),
	'LT'	=> array('Litwa','Litwa'),
	'LV'	=> array('Łotwa','Lotwa'),
	'L'	=> array('Luksemburg','Luksemburg'),
	'MK'	=> array('Macedonia','Macedonia'),
	'MD'	=> array('Mołdawia','Moldawia'),
	'D'	=> array('Niemcy','Niemcy'),
	'N'	=> array('Norwegia','Norwegia'),
	'PL'	=> array('Polska','Polska'),
	'P'	=> array('Portugalia','Portugalia'),
	'RUS'	=> array('Rosja','Rosja'),
	'RO'	=> array('Rumunia','Rumunia'),
	'SCG'	=> array('Serbia','Serbia'),
	'SLO'	=> array('Słowenia','Slowenia'),
	'SK'	=> array('Słowacja','Slowacja'),
	'CH'	=> array('Szwajcaria','Szwajcaria'),
	'S'	=> array('Szwecja','Szwecja'),
	'TR'	=> array('Turcja','Turcja'),
	'UA'	=> array('Ukraina','Ukraina'),
	'H'	=> array('Węgry','Wegry'),
	'GB'	=> array('Wielka Brytania','Wielka Brytania'),
	'I'	=> array('Włochy','Wlochy'),
);
$ccONZ2ISO = array( // 41 kraje
	'AL'	=> array('al','Albania'),
	'A'		=> array('at','Austria'),
	'B'		=> array('be','Belgium'),
	'BY'	=> array('bo','Belarus'),
	'BIH'	=> array('ba','Bosnia and Herzegovina'),
	'BG'	=> array('bg','Bulgaria'),
	'HR'	=> array('hr','Croatia'),
	'MNE'	=> array('me','Montenegro'),
	'CZ'	=> array('cz','Czech Republic'),
	'DK'	=> array('dk','Denmark'),
	'EST'	=> array('ee','Estonia'),
	'RU'	=> array('ru','Russian Federation'),
	'FIN'	=> array('fi','Finland'),
	'F'		=> array('fr','France'),
	'GR'	=> array('gr','Greece'),
	'E'		=> array('es','Spain'),
	'NL'	=> array('nl','Netherlands'),
	'IRL'	=> array('ie','Ireland'),
	'KZ'	=> array('kz','Kazakhstan'),
	'FL'	=> array('li','Liechtenstein'),
	'LT'	=> array('lt','Lithuania'),
	'LV'	=> array('lv','Latvia'),
	'L'		=> array('lu','Luxembourg'),
	'MK'	=> array('mk','Macedonia'),
	'MD'	=> array('md','Moldova'),
	'D'		=> array('de','Germany'),
	'N'		=> array('no','Norway'),
	'PL'	=> array('pl','Poland'),
	'P'		=> array('pt','Portugal'),
	'RUS'	=> array('ru','Russian Federation'),
	'RO'	=> array('ro','Romania'),
	'SCG'	=> array('rs','Serbia'),
	'SLO'	=> array('si','Slovenia'),
	'SK'	=> array('sk','Slovakia'),
	'CH'	=> array('ch','Switzerland'),
	'S'		=> array('se','Sweden'),
	'TR'	=> array('tr','Turkey'),
	'UA'	=> array('ua','Ukraine'),
	'H'		=> array('hu','Hungary'),
	'GB'	=> array('gb','United Kingdom'),
	'I'		=> array('it','Italy'),
);

function GetCountryONZ($name) {
	global $ccONZ;
	// Nie dziala przy UTF: array_search, in_array
	foreach($ccONZ as $k => $v) {
		foreach($v as $cc) {
			if($name == $cc) return $k;
		}
	}
	return "[$name]";
}

function FixTitle($s, $cc='PL') {
	global $ccONZ;
	if($s=='') $s = 'Bez nazwy';

	$table = array(
		array($ccONZ[$cc][0], ''),
		array($ccONZ[$cc][1], ''),
		array('>', ''), // TomTom traktuje wszystko za > jako nr telefonu
	);
	foreach($table as $v) {$find[]=$v[0];$repl[]=$v[1];}
	$s = str_ireplace($find, $repl, $s);

	unset($find,$repl);
	$table = array(
		array("'(\s)$cc(\s)'"				, "\\1\\2"), // Usuń kod kraju w środku (case-sensitive)
		array("'([\W]+)$cc([\W\s])?$'i"	, "\\1\\2"), // Usuń kod kraju jesli na końcu (case-insensitive)
	);
	foreach($table as $v) {$find[]=$v[0];$repl[]=$v[1];}
	$s = preg_replace($find, $repl, $s);

	$s = FixComments($s);
	return $s;
}

function FixComments($s) {
	$table = array(
		array("'\([\s]+'"	,	"("	), // Spacje przed
		array("'[\s]+\)'"	,	")"	), // i po nawiasach
		array("'\s,'"		,	","	), // Spacje przed przecinkami
		array("'[\.]{3,}'"	,	","	), // Potrójne kropki
		array("'[\s]{2,}'"	,	" "	), // Podwójne spacje
		array("'[-]{2,}'"	,	"-"	), // Podwójne myślniki
		array("'[_]{2,}'"	,	"-"	), // Podwójne podkreślniki
		array("'\(\)'"		,	""	), // Puste nawiasy
	);
	foreach($table as $v) {$find[]=$v[0];$repl[]=$v[1];}
	$s = preg_replace($find, $repl, $s);
	$s = trim($s, " \t.,-/\\");
	return ucfirst_($s);
}

function FixFilename($s) {
	$s = utf2ascii($s);
	$s = strtolower($s);
	$table = array(
		array('/[\W]/'		,	' '	), // Non word
		array('/[\s]{2,}/'	,	' '	), // Podwójne spacje
	);
	foreach($table as $v) {$find[]=$v[0];$repl[]=$v[1];}
	$s = preg_replace($find, $repl, $s);

	$s = str_replace(' ', '_', $s);
	$s = trim($s, '_');
	return $s;
}

function FixZakazyTable($s) {
	$table = array(
		array("Dzień tyg."		,	"Dzień"		),
		array("Godziny zakazu"	,	"Godziny"	),
		array("Dodatkowe info."	,	"Info"		),
		array("2012-"			,	"12-"		),
		array("Poniedziałek"	,	"Pn"),
		array("Wtorek"			,	"Wt"),
		array("Środa"			,	"Śr"),
		array("Czwartek"		,	"Cz"),
		array("Piątek"			,	"Pt"),
		array("Sobota"			,	"So"),
		array("Niedziela"		,	"Nd"),
	);
	foreach($table as $v) {$find[]=$v[0];$repl[]=$v[1];}
	$s = str_ireplace($find, $repl, $s);
	return $s;
}

function FixCategory($s) {
	$s = str_ireplace(array(
		'różne',
		'do ustalenia',
	), 'inne', $s);
	return $s;
}

function iso2utf($in) {
	return iconv('ISO-8859-2', 'UTF-8', $in);
}
function utf2iso($in) {
	return iconv('UTF-8', 'ISO-8859-2', $in);
}
function utf2dos($in) {
	return iconv('UTF-8', 'CP852//TRANSLIT', $in);
}
function utf2ascii($in) {
	// Zastąp polskie ogonki znakami ASCII np. ó -> o. iconv dodaje cudzysłów przez każdym takim znakiem, wiec usuwamy...
	return str_replace("'", '', iconv('UTF-8', 'ASCII//TRANSLIT', $in));
}
function utf2txt($in) {
	return iconv('UTF-8', 'CP1252', $in);
}
function ucfirst_($s) {
	$s = iconv('UTF-8', 'CP1250', $s);
	$s = ucfirst($s);
	$s = iconv('CP1250', 'UTF-8', $s);
	return $s;
}
function strtoupper_($s) {
	return mb_strtoupper($s,'UTF-8');
}
function strtolower_($s) {
	return mb_strtolower($s,'UTF-8');
}
function myhtmlentities($s) {
	return htmlspecialchars(unhtmlentities($s), ENT_QUOTES | ENT_XHTML, 'UTF-8');
	//return htmlspecialchars(unhtmlentities($s));
	//return "htmlspecialchars($s)";
}
function log_escape_helper($s) {
    return str_replace(
            array( '\\'),
            array('&#92;'),
            $s
        );
}
function unhtmlentities($s) {
	return htmlspecialchars_decode($s, ENT_QUOTES);
}
// Convert HTML entities like &#8211; to their character equivalents
function my_html_entity_decode($s) {
	return html_entity_decode($s, ENT_NOQUOTES, 'UTF-8');
}
// removes files and non-empty directories
function rrmdir($dir) {
    // return is_file($path)? @unlink($path): array_map('rrmdir',glob($path.'/*'))==@rmdir($path);
	static $cfiles = 0;
	if(is_dir($dir)) {
		$files = scandir($dir);
		foreach($files as $file) if(!in_array($file, array('.','..'))) rrmdir("$dir/$file");
		rmdir($dir);
	}
	elseif(is_file($dir)) if(unlink($dir)) $cfiles++;
	return $cfiles;
}
// copy files and non-empty directories
function rcopy($src, $dst) {
	static $cfiles = 0;
	//if(file_exists($dst)) rrmdir($dst);
	if(is_dir($src)) {
		@mkdir($dst);
		$files = scandir($src);
		foreach($files as $file) if(strpos($file,'.')!==0) rcopy("$src/$file", "$dst/$file");
	}
	elseif(is_file($src)) if(copy($src, $dst)) $cfiles++;
	return $cfiles;
}

// Lepsze mieszanie playlist
function ork_shuffle(&$a) {
	$count = count($a);
	if(!$count) return false;

	$out = array();

    // Generuj unikalne klucze
    foreach($a as $v) {
        $k = sprintf('%u', crc32($v));
		$k = str_shuffle($k);
        $out[$k] = $v;
    }
    
    // Soruj klucze, czyli mieszaj wartosci!
    ksort($out, SORT_NUMERIC);
    
    // Usun klucze
    $a = array_merge(array(), $out);

	//$a = $out;
    return true;
}
