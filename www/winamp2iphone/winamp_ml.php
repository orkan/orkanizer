<?php
error_reporting(E_ALL ^ E_NOTICE);

$title = 'Oczyszczarka Media Library';
$desc = '
 * Naprawia strukturę playlist, usuwa nieaktualne playlisty do kopii zapasowej ZIP
 * Skanuje każdą playlistę w poszukiwaniu błędów
 * Automatycznie usuwa nieaktualne wpisy ścieżek do utworów
 * INFO:
 * Ten skrypt zmienia ilość ścieżek w playlistach. Żeby je ponownie obliczyć
   kliknij na playlistę w ML - Winamp zrobi to automatycznie (tylko kosmetyka)
';
$name = 'winamp_ml';

require('../shared/php/functions.php');
require('../shared/php/templates.php');
require('../shared/php/zip.php');

// ========================
// Konfiguracja:
$cfg = array(
	'title'             => $title,
	'settings_file'     => "{$name}.dat",
	'tmp_dir'           => '../shared/tmp/',
	'zip_src_mask'		=> "{$name}_%s/",
	'zip_file_mask'		=> "{$name}_backup_%s.zip",
	'log_name'		    => "{$name}.log",
	'ml_pls'		    => 'playlists.xml',
	'ml_m3u_patt'       => 'plf*.m3u8',
    'fields'            => array(
                            'winamp_dir'        => '',
                            'music_dir'         => '',
                            'fix_orphaned'      => '0', // osierocone pl w ML
                            'fix_duplicates'    => '0', // duplikaty utworow w pl
                            'fix_missing'       => '0', // zaginone utwory w pl
                            'fix_sort'          => '0', // sortuj utwory w pl
                        ),
	'task'              => ' -&gt; ',
);

$dat = @file_get_contents($cfg['settings_file']);
$dat = $dat ? unserialize($dat) : array();
$dat['cdate'] = date('Ymd_His', time());

// ========================
// Zastap domyslne ustawienia z $cfg, ustawieniami uzytkownika zapisanymi w $dat
$dat['fields'] = array_merge($cfg['fields'], is_array($dat['fields']) ? $dat['fields'] : array());

$err = array();
$log = array();
$t = new XTemplate("{$name}.tpl");

// ========================
// array_walk - przenoszenie plikow
function MoveFiles_callback(&$v, $k, $dest) {
    $file = basename($v);
    rename($v, $dest.$file);
    $v = $file;
}
// ========================
// usort - Sortuj utwory wg. nazwy pliku
function CmpByFilename($a, $b) {
    return strcmp(basename($a), basename($b));
}
// ========================
// array_udiff - porownaj sciezki do utworow
function CmpPlaylists($a, $b) {
    return strcasecmp($a, $b);
}
// ========================
//
function print_arr($arr, $tmp) {
    foreach($arr as $v)
        $s[] = " [".sprintf('%03d', ++$i)."] => {$v}";
    return implode("\n", $s);
}

// ========================
// [Konwertuj!]
// ========================
if($_SERVER["REQUEST_METHOD"] == 'POST' && !empty($_POST['winamp_dir']))
{
    // ========================
    // Zapisz dane uzytkownika
    $dat['fields']['winamp_dir']        = $_POST['winamp_dir'];
    $dat['fields']['fix_orphaned']      = $_POST['fix_orphaned'];
    $dat['fields']['fix_duplicates']    = $_POST['fix_duplicates'];
    $dat['fields']['fix_missing']       = $_POST['fix_missing'];
    $dat['fields']['fix_sort']          = $_POST['fix_sort'];
    $dat['fields']['music_dir']         = $_POST['music_dir'];
    $dat['fields']['fix_path']          = $_POST['fix_path'];
    $dat['fields']['fix_path_from']     = $_POST['fix_path_from'];
    $dat['fields']['fix_path_to']       = $_POST['fix_path_to'];
    $dat['fields']['fix_dead']          = $_POST['fix_dead'];

    $dat['fields']['winamp_dir_fixed']  = rtrim($dat['fields']['winamp_dir'], '/\\').'/';
    $dat['fields']['music_dir_fixed']   = rtrim($dat['fields']['music_dir'], '/\\').'/';

    // ========================
    // Katalog roboczy...
    $cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
    $cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);

    $log[] = 'Tworzę katalog roboczy: '.$cfg['zip_src_path'];
    rrmdir($cfg['zip_src_path']); // folder roboczy
    mkdir($cfg['zip_src_path'], 0777, true);

    // ========================
    // Przygotuj pliki wejsciowe i backup
    $log[] = "Kopiuję {$cfg['ml_pls']}";
    if(!copy($dat['fields']['winamp_dir_fixed'].$cfg['ml_pls'], $cfg['zip_src_path'].$cfg['ml_pls']))
        ShowErrors("Błąd kopiowania {$cfg['ml_pls']}");

    $m3us = glob($dat['fields']['winamp_dir_fixed'].$cfg['ml_m3u_patt']);
    array_walk($m3us, 'MoveFiles_callback', $cfg['zip_src_path']);
    $log[] = 'Przenoszę playlisty do katalogu roboczego ['.count($m3us).']';

    $log[] = 'Zapisuję backup do: '.$cfg['zip_out_path'];
    $zip = new ZipFolder($cfg['zip_out_path'], $cfg['zip_src_path']);

    $log[] = str_repeat('-', 100);

    // ========================
    // Statystyki
    $stats = array(
        'm3us' => array(
            'found' => array(),
            'miss'  => array(),
            'orph' => 0,
        ),
        'mp3s' => array(
            'miss'  => 0,
            'dupes' => 0,
        ),
    );

    // ========================
    // Wczytaj plik XML z informacjami o playlistach ML
    $playlists = simplexml_load_file($cfg['zip_src_path'].$cfg['ml_pls']);

    // ========================
    // Zastosuj kolejke na kazdej playliscie
    // ========================
    foreach($playlists->playlist as $playlist)
    {
        $log[] = '';
        $log[] = "Przetwarzam playlistę &laquo;[{$playlist['title']}]&raquo; plik [{$playlist['filename']}], utworów [".Nbr((double)$playlist['songs'])."], czas [".FormatTime((double)$playlist['seconds'])."]";

        $m3u = $cfg['zip_src_path'].$playlist['filename'];

        if(!is_file($m3u)) {
            $log[] = '!!! Nie mogę odnaleźć pliku! Kontynuuję...';
            $stats['m3us']['miss'][] = $playlist['filename'];
            continue;
        }

        $p1 = ImportPlaylist($m3u);
        $stats['mp3s']['valid_1'] += count($p1);

        // ========================
        // Usuń zdublowane utwory w playlistach oraz sortuj
        if($dat['fields']['fix_duplicates'])
        {
            $dupes = array();
            foreach(array_count_values($p1) as $k => $v) {
                if($v>1) $dupes[] = $k;
            }
            $p2 = array_unique($p1);

            $log[] = $cfg['task'].'Usuwam duplikaty i sortuję: przed ['.count($p1).'], po ['.count($p2).'], różnica ['.count($dupes).']';
            if(count($dupes)) {
                $log[] = print_arr($dupes, true);
                $stats['mp3s']['dupes'] += count($dupes);
            }
            $p1 = $p2;
        }
        else $stats['mp3s']['dupes'] = '-';

        // ========================
        // Sortuj utwory wg. nazwy pliku
        if($dat['fields']['fix_sort'])
        {
            $log[] = $cfg['task'].'Sortuję wg. nazw plików...';
            usort($p1, 'CmpByFilename');
        }

        // ========================
        // Podmień ścieżkę do pliku
        if($dat['fields']['fix_path'] && !empty($_POST['fix_path_from']) && !empty($_POST['fix_path_to']))
        {
            $log[] = $cfg['task']."Zmieniam ścieżki z [{$dat['fields']['fix_path_from']}] na [{$dat['fields']['fix_path_to']}]";
            foreach($p1 as $k => $v) {
                $p1[$k] = str_ireplace($dat['fields']['fix_path_from'], $dat['fields']['fix_path_to'], $v);
            }
        }

        // ========================
        // Sprawdz jakie utwory zostaly odrzucone przy wczytywaniu tej playlisty
        $p2 = ImportPlaylist($m3u, true);
        $diff = array_udiff($p2, $p1, 'CmpPlaylists');
        $miss = count($diff);

        $log[] = 'Zapisuję utwory: ['.count($p1).'], nieodnalezione: ['.$miss.']';
        if($miss) {
            $log[] = print_arr($diff, true);
            $stats['mp3s']['miss'] += $miss;
        }

        // Zapis playlisty...
        file_put_contents($m3u, implode("\n", $p1));
        $stats['mp3s']['valid_2'] += count($p1);

        // Znaleziona...
        $stats['m3us']['found'][] = $playlist['filename'];
    }

    // ========================
    // Usuń sieroty w katalogu ML
    if($dat['fields']['fix_orphaned'])
    {
        $orph = array();
        foreach($m3us as $file)
        {
            if(in_array($file, $stats['m3us']['found'])) continue;
            $orph[] = $file;
            unlink($cfg['zip_src_path'].$file);
        }

        $log[] = '';
        $log[] = str_repeat('-', 100);
        $log[] = 'Usuwam osierocone playlisty:';

        if($orphaned = count($orph)) {
            sort($orph);
            $log[] = print_arr($orph, true);
            $stats['m3us']['orph'] = count($orph);
        }
    }
    else $stats['m3us']['orph'] = '-';

    // ========================
    // Przywroc dobre playlisty
    $m3us_new = glob($cfg['zip_src_path'].$cfg['ml_m3u_patt']);
    array_walk($m3us_new, 'MoveFiles_callback', $dat['fields']['winamp_dir_fixed']);
    $log[] = '';
    $log[] = str_repeat('-', 100);
    $log[] = 'Przenoszę playlisty do katalogu ML ['.count($m3us_new).']';

    // ========================
    // Podsumowanie
    // ========================
    $stats['mp3s']['all'] = $stats['mp3s']['valid_1'] + $stats['mp3s']['miss'];
    $log[] = str_repeat('-', 100);
    $log[] = 'Znalazłem ['.count($m3us).'] palylist, w tym ['.$stats['m3us']['orph'].'] osierocone.';
    $log[] = 'Znalazłem ['.$stats['mp3s']['all'].'] utworów, w tym ['.$stats['mp3s']['dupes'].'] zdublowane i ['.$stats['mp3s']['miss'].'] nieodnalezione.';
    $log[] = 'Aktualnie w ['.count($stats['m3us']['found']).'] palylistach znajduje się ['.$stats['mp3s']['valid_2'].'] utworów.';

    // ========================
    // Dodaj log do backupu
    // ========================
    $txt = array();
    $txt[] = "$title © ".date('Y', time())." Orkan";
    $txt[] = '';
    $txt[] = html_entity_decode(implode("\n", $log));
    $txt[] = '';
    $txt[] = 'PHP: '.phpversion().' (Czas skryptu: '.FormatTime(get_execution_time()).')';
    $txt[] = date('r', time());
    $zip->addFromString("{$name}.log", html_entity_decode(implode("\n", $txt)));

    // ========================
    // Sprzatanie...
    // ========================
    if(is_file($cfg['zip_out_path'])) {
        $log[] = str_repeat('-', 100);
        $log[] = 'Usuwam katalog roboczy: '.realpath($cfg['zip_src_path']);
        rrmdir($cfg['zip_src_path']);
    }

    $t->assign(array(
        'LOG' => implode("\n", array_map('log_escape_helper', $log)),
    ));
    $t->parse('MAIN.DONE');
}


// ========================
// Wyswietl strone glowna
// ========================
else
{
    $t->assign(array(
        'WINAMP_DIR'        => $dat['fields']['winamp_dir'],
        'FIX_ORPHANED'      => $dat['fields']['fix_orphaned'] ? 'checked="checked"' : '',
        'FIX_DUPLICATES'    => $dat['fields']['fix_duplicates'] ? 'checked="checked"' : '',
        'FIX_SORT'          => $dat['fields']['fix_sort'] ? 'checked="checked"' : '',
        'FIX_MISSING'       => $dat['fields']['fix_missing'] ? 'checked="checked"' : '',
        'MUSIC_DIR'         => $dat['fields']['music_dir'],
        'FIX_PATH'          => $dat['fields']['fix_path'] ? 'checked="checked"' : '',
        'FIX_PATH_FROM'     => $dat['fields']['fix_path_from'],
        'FIX_PATH_TO'       => $dat['fields']['fix_path_to'],
        'FIX_DEAD'          => $dat['fields']['fix_dead'] ? 'checked="checked"' : '',
    ));
    $t->parse('MAIN.FORM');
}


// ========================
// Koncowy render
// ========================
$t->parse('MAIN');
$t->out('MAIN');

file_put_contents($cfg['settings_file'], serialize($dat));
