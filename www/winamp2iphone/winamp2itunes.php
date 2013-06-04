<?php
error_reporting(E_ALL ^ E_NOTICE);

$title = 'Winamp do iTunes playlist generator';
$desc = '
 * Pobiera dwie playlisty: Ulubione i Pozostale
   i tworzy dwie playlisty: Ulubione i Wszystkie:
     Ulubione.m3u8 - ta sama playlista z poprawionymi sciezkami do plików
     Wszystkie.m3u8 - zawiera Ulubione i Pozostale (dobrane losowo) tak aby ilosc nie przekroczyla podanego limitu.
 * Można wskazać oryginalne playlisty z kat. Winampa [C:\Documents and Settings\Administrator\Application Data\Winamp\Plugins\ml\*.m3u8] 
   albo wyeksportować z Media Library.
 * Nie zapomnij najpierw włączyć <a href="winamp_ml.php">oczyszczarki ML</a>
';
$name = 'winamp2itunes';

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
	'zip_file_mask'		=> 'Winamp2iTunes_%s.m3u8.zip',
	'fav_name'		    => 'Ulubione.m3u8',
	'fav_name_rnd'		=> 'Ulubione%s.m3u8',
	'all_name'		    => 'Wszystkie.m3u8',
	'all_name_rnd'		=> 'Wszystkie%s.m3u8',
	'log_name'		    => "{$name}.log",
    'fields'            => array(
                            'fav_pls' => 'C:\Documents and Settings\Administrator\Application Data\Winamp\Plugins\ml\plf0001.m3u8',
                            'col_pls' => 'C:\Documents and Settings\Administrator\Application Data\Winamp\Plugins\ml\plf0002.m3u8',
                            'is_maxsize' => '0',
                            'max_quantity' => 1000,
                            'max_size' => 2000,
                            'fav_files' => 3,
                            'all_files' => 1,
                        ),
);

$dat = @file_get_contents($cfg['settings_file']);
$dat = $dat ? unserialize($dat) : array();
$dat['cdate'] = date('Ymd', time());

// ========================
// Zastap domyslne ustawienia z $cfg, ustawieniami uzytkownika zapisanymi w $dat
$dat['fields'] = array_merge($cfg['fields'], is_array($dat['fields']) ? $dat['fields'] : array());

$cfg['zip_src_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_src_mask'], $dat['cdate']);
$cfg['zip_out_path'] = $cfg['tmp_dir'].sprintf($cfg['zip_file_mask'], $dat['cdate']);

$errors = array();
$log = array();
$t = new XTemplate("{$name}.tpl");


// ========================
// [Generuj!]
// ========================
if($_SERVER["REQUEST_METHOD"] == 'POST')
{
    // ========================
    // Katalog roboczy
    $log[] = 'Tworzę katalog roboczy: '.$cfg['zip_src_path'];
    $log[] = 'Usuwam archiwum: '.$cfg['zip_out_path'];

    rrmdir($cfg['zip_src_path']); // folder roboczy
    rrmdir($cfg['zip_out_path']); // wyjściowy zip
    mkdir($cfg['zip_src_path'], 0777, true);

    // ========================
    // Zapisz dane uzytkownika
    $dat['fields']['fav_pls']           = $_POST['fav_pls'];
    $dat['fields']['col_pls']           = $_POST['col_pls'];
    $dat['fields']['is_maxsize']        = $_POST['is_maxsize'];
    $dat['fields']['max_quantity']      = $_POST['max_quantity'];
    $dat['fields']['max_size']          = $_POST['max_size'];
    $dat['fields']['fav_files']         = $_POST['fav_files'];
    $dat['fields']['all_files']         = $_POST['all_files'];

/*
file_put_contents($cfg['settings_file'], serialize($dat));
exit;
*/

    $favs = ImportPlaylist($dat['fields']['fav_pls']);
    $cols = ImportPlaylist($dat['fields']['col_pls']);

    $log[] = 'Importuję playlisty: Ulubione ['.Nbr(count($favs)).' utworów], Kolekcja ['.Nbr(count($cols)).' utworów]. Razem ['.Nbr(count($favs)+count($cols)).']';

    // Sortuj i usun duplikaty
    $favs = array_unique($favs);
    $cols = array_unique($cols);

    $log[] = 'Sortuję i usuwam duplikaty: Ulubione ['.Nbr(count($favs)).' utworów], Kolekcja ['.Nbr(count($cols)).' utworów]. Razem ['.Nbr(count($favs)+count($cols)).']';

    // Pomieszaj Kolekcje zeby za kazdym razem otrzymac inny zestaw plikow
    $log[] = 'Mieszam Kolekcję...';
    shuffle($cols);
    
    // Liczniki
    $alls = array();
    $all_size = 0;
    $all_count = 0;

    // ========================
    // Polacz wg. limitu rozmiaru
    if($dat['fields']['is_maxsize'])
    {
        $max_size = (($dat['fields']['max_size']>1000) ? round(($dat['fields']['max_size']/1000)*1024) : $dat['fields']['max_size']) * pow(2, 20); // to Bytes
        $log[] = 'Łączę playlisty do rozmiaru '.FormatBytes($max_size);

        // favs
        for($i=0; $i<count($favs); $i++)
        {
            $size = @filesize($favs[$i]);
            if($size === false) {
                $log[] = "Błąd pliku: [{$favs[$i]}] pomijam... \$favs[$i]";
                continue;
            }

            if($all_size + $size <= $max_size)
            {
                $alls[] = $favs[$i];
                $all_size += $size;
            }
            else break;
        }
        // cols
        for($i=0; $i<count($cols); $i++)
        {
            $size = filesize($cols[$i]);
            if($all_size + $size <= $max_size)
            {
                // Pomin duplikaty
                if(array_search($cols[$i], $alls) !== false) continue;

                $alls[] = $cols[$i];
                $all_size += $size;
            }
            else break;
        }
    }
    // ========================
    // Polacz wg. limitu ilosci
    else
    {
        $log[] = 'Łączę playlisty do ilości '.Nbr($dat['fields']['max_quantity']).' utworów';

        if(count($favs) > $dat['fields']['max_quantity'])
        {
            $alls = array_slice($favs, 0, $dat['fields']['max_quantity']);
        }
        else
        {
            for($i=0; $i<count($favs); $i++)
            {
                if($all_count + 1 <= $dat['fields']['max_quantity'])
                {
                    $alls[] = $favs[$i];
                    $all_count++;
                }
                else break;
            }
            for($i=0; $i<count($cols); $i++)
            {
                if($all_count + 1 <= $dat['fields']['max_quantity'])
                {
                    // Pomin duplikaty
                    if(array_search($cols[$i], $alls) !== false) continue;

                    $alls[] = $cols[$i];
                    $all_count++;
                }
                else break;
            }
        }

        foreach($alls as $k => $v) {
            $size = @filesize($v);
            if($size === false) {
                $log[] = "Błąd pliku: [{$v}] pomijam... \$alls[$k]";
                continue;
            }
        
            $all_size += $size;
        }
    }

    $log[] = 'Łączny romiar '.FormatBytes($all_size).' w '.Nbr(count($alls)).' utworach';

    
    // ========================
    // Oryginaly playlist
    $log[] = 'Zapisuję playlisty: Ulubione i Wszystkie';
    
    // Skroc Kolekcje to podanego limitu
    $cols = $alls;
    sort($cols);
    file_put_contents($cfg['zip_src_path'].$cfg['fav_name'], implode("\n", $favs));
    file_put_contents($cfg['zip_src_path'].$cfg['all_name'], implode("\n", $cols));
    
    
    // ========================
    // Kopie playlist z losowa kolejnoscia
    $dat['fields']['fav_files'] = max($dat['fields']['fav_files'], 0);
    $dat['fields']['all_files'] = max($dat['fields']['all_files'], 0);

    $log[] = 'Tworzę '.Nbr($dat['fields']['fav_files']).' playlist Ulubionych z losową kolejnością';
    for($i=1; $i<=$dat['fields']['fav_files']; $i++)
    {
        $rand = $favs;
        shuffle($rand);
        file_put_contents($cfg['zip_src_path'].sprintf($cfg['fav_name_rnd'], $i), implode("\n", $rand));
    }

    $log[] = 'Tworzę '.Nbr($dat['fields']['all_files']).' playlist Wszystkich z losową kolejnością';
    for($i=1; $i<=$dat['fields']['all_files']; $i++)
    {
        $rand = $alls;
        shuffle($rand);
        file_put_contents($cfg['zip_src_path'].sprintf($cfg['all_name_rnd'], $i), implode("\n", $rand));
    }


    // ========================
    // Zapis i sprzatanie
    // ========================
    file_put_contents(
        $cfg['zip_src_path'].$cfg['log_name'],
        "$title © ".date('Y', time())." Orkan\n\n".implode("\n", $log)."\n\nPHP: ".phpversion()."\n".date('r', time())
    );

    new ZipFolder($cfg['zip_out_path'], $cfg['zip_src_path']);
    $log[] = 'Tworzę archiwum: '.realpath($cfg['zip_out_path']);

    if(is_file($cfg['zip_out_path'])) {
        $log[] = 'Usuwam katalog roboczy: '.realpath($cfg['zip_src_path']);
        rrmdir($cfg['zip_src_path']);
    }

    $t->assign(array(
        'LOG'       => implode("\n", $log),
        'ZIP_FILE'  => $cfg['zip_out_path'],
    ));
    $t->parse('MAIN.DONE');
}


// ========================
// Wyswietl strone glowna
// ========================
else
{
    $t->assign(array(
        'FAV_PLS'       => $dat['fields']['fav_pls'],
        'COL_PLS'       => $dat['fields']['col_pls'],
        'DRIVE'         => $dat['fields']['drive'],
        'MAX_QUANTITY'  => $dat['fields']['max_quantity'],
        'MAX_SIZE'      => $dat['fields']['max_size'],
        'IS_MAXSIZE'    => $dat['fields']['is_maxsize'],
        'FAV_FILES'     => $dat['fields']['fav_files'],
        'ALL_FILES'     => $dat['fields']['all_files'],
    ));
    $t->parse('MAIN.FORM');
}


// ========================
// Koncowy render
// ========================
$t->parse('MAIN');
$t->out('MAIN');

file_put_contents($cfg['settings_file'], serialize($dat));
