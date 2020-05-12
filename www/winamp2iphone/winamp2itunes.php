<?php
error_reporting(E_ALL ^ E_NOTICE);

$name = 'winamp2itunes';
$title = "Generator playlist Winamp > iTunes";

require('../shared/php/functions.php');
require('../shared/php/templates.php');
require('../shared/php/zip.php');
require('../shared/php/ajax.php');

// ========================
// Konfiguracja:
$cfg = array(
	'title'             => $title,
	'cdate'             => date('Ymd', time()),
	'cfg_name'          => "{$name}.dat",
	'dir_path'          => '../shared/tmp/',
	'zip_src_mask'		=> "{$name}_%s/",
	'zip_out_mask'		=> 'Winamp2iTunes_%s.m3u8.zip',
	'log_name'		    => "{$name}.log",
	'log_intro'		    => sprintf("# $title © 2013-%s Orkan\n\n", date('Y', time())),
	'countall_mask'	    => 'Razem: %1$s w %2$s utworach.',
	'pls_winamp'	    => 'playlists.xml',
	'dir_winamp'	    => '', // placeholder
    'fields'            => array(
                            'dir_winamp' => 'C:\Users\Administrator\AppData\Roaming\Winamp\Plugins\ml',
                            'pls_top' => 'plf0000.m3u8',
                            'pls_fav' => 'plf0001.m3u8',
                            'pls_col' => 'plf0002.m3u8',
                            'is_addtop' => '1', // Dodaj [Najlepsze] do [Ulubione]
                            'is_remfav' => '1', // Usuń [Ulubione] z [Kolekcja]
                            'is_maxtop' => '0',
                            'maxtop' => 999,    // Ogranicz [Najlepsze] do
                            'is_maxfav' => '0',
                            'maxfav' => 800,    // Ogranicz [Ulubione] do
                            'is_totsize' => '1',
                            'totsize' => 10000, // Ogranicz [Wszystkie] do
                            'export_top' => 1,
                            'export_fav' => 6,
                            'export_col' => 1,
                            'export_all' => 1,
                            'pls_winamp' => '',
                        ),
);

$tmp = @file_get_contents($cfg['cfg_name']);
$tmp = $tmp ? unserialize($tmp) : array();

// ========================
// Zastap domyslne ustawienia, ustawieniami z pliku konfiguracyjnego [.dat]
$cfg['fields'] = array_merge($cfg['fields'], is_array($tmp['fields']) ? $tmp['fields'] : array());

$cfg['dir_winamp'] = rtrim($cfg['fields']['dir_winamp'], '/\\').'/';
$cfg['zip_src'] = $cfg['dir_path'].sprintf($cfg['zip_src_mask'], $cfg['cdate']);
$cfg['zip_out'] = $cfg['dir_path'].sprintf($cfg['zip_out_mask'], $cfg['cdate']);

$errors = array();
$log = array();
$t = new XTemplate("{$name}.tpl");

// ========================
// Playlisty - od "najważniejszej". Patrz limit całości.
$pls = array(
    'TOP' => array('id' => 'top', 'items' => array(), 'limit' => $cfg['fields']['is_maxtop'] ? $cfg['fields']['maxtop'] : 0, 'count' => 0, 'title' => 'Najlepsze'),
    'FAV' => array('id' => 'fav', 'items' => array(), 'limit' => $cfg['fields']['is_maxfav'] ? $cfg['fields']['maxfav'] : 0, 'count' => 0, 'title' => 'Ulubione'),
    'COL' => array('id' => 'col', 'items' => array(), 'limit' => $cfg['fields']['is_maxcol'] ? $cfg['fields']['maxcol'] : 0, 'count' => 0, 'title' => 'Kolekcja')
);
$all = array('id' => 'all', 'items' => array(), 'limit' => $cfg['fields']['is_totsize'] ? $cfg['fields']['totsize'] : 0, 'title' => 'Wszystkie');


// ========================
// [AJAX]
// ========================
if($_SERVER["HTTP_X_REQUESTED_WITH"])
{
    switch($_GET['m'])
    {
        case 'countall':
            foreach($pls as $k => $v) {
                $pls[$k]['items'] = ImportPlaylist($cfg['dir_winamp'].$cfg['fields']["pls_{$v['id']}"]);
                $pls[$k]['count'] = count($pls[$k]['items']);
                $all['items'] = array_merge($all['items'], $pls[$k]['items']); // Połącz playlisty
            }
            $all['items'] = array_unique($all['items']);
            $all['size'] = 0;
            foreach($all['items'] as $v) {
                $all['size'] += filesize($v);
            }
            $tmp = array();
            foreach($pls as $k => $v) {
                $tmp[] = "{$pls[$k]['title']} [{$pls[$k]['count']}]";
            }
            ajax(implode(', ', $tmp).'. '.sprintf($cfg['countall_mask'], FormatBytes($all['size']), count($all['items'])));
        break;
    }
    exit;
}
// ========================
// [Generuj!]
// ========================
elseif($_SERVER["REQUEST_METHOD"] == 'POST')
{
    // ========================
    // Zapisz dane użytkownika
    foreach($cfg['fields'] as $k => $v) {
        $cfg['fields'][$k] = $_POST[$k] ? $_POST[$k] : 0;
    }
        
    // ========================
    // Katalog roboczy
    $log[] = 'Przygotowuję katalog roboczy: '.$cfg['zip_src'];
    rrmdir($cfg['zip_src']); // folder roboczy
    rrmdir($cfg['zip_out']); // wyjściowy zip
    mkdir($cfg['zip_src'], 0777, true);

    
    // ========================
    // Załaduj playlisty
    $log[] = 'Importuję playlisty i usuwam duplikaty:';
    $tmp1 = $tmp2 = 0;
    foreach($pls as $k => $v)
    {
        // Pobierz playlisty
        $pls[$k]['items'] = ImportPlaylist($cfg['dir_winamp'].$cfg['fields']["pls_{$v['id']}"]);
        
        $pls[$k]['count_ini'] = count($pls[$k]['items']);
        
        // Usun duplikaty (sortuje!)
        $pls[$k]['items'] = array_unique($pls[$k]['items']);
        $pls[$k]['count_dup'] = count($pls[$k]['items']);
        
        $log[] = "\t{$pls[$k]['title']}: {$pls[$k]['count_ini']} - ".($pls[$k]['count_ini'] - $pls[$k]['count_dup'])." = {$pls[$k]['count_dup']} pozycji";
        $tmp1 += $pls[$k]['count_ini'];
        $tmp2 += $pls[$k]['count_dup'];
        
        $pls[$k]['count']       = $pls[$k]['count_dup'];
        $pls[$k]['count_fix']   = $pls[$k]['count_dup'];
    }
    $log[] = "\tRazem: $tmp1 - ".($tmp1-$tmp2)." = $tmp2 pozycji";

    
    // ========================
    // Opcje: Dodaj [Najlepsze] do [Ulubione]
    if($cfg['fields']['is_addtop'])
    {
        $pls['FAV']['items'] += $pls['TOP']['items'];
        $pls['FAV']['items'] = array_unique($pls['FAV']['items']);
        $pls['FAV']['count_add'] = count($pls['FAV']['items']);
        
        $log[] = "Do [{$pls['FAV']['title']}] dodaję [{$pls['TOP']['title']}]: {$pls['FAV']['count']} + ".($pls['FAV']['count_add'] - $pls['FAV']['count'])." = {$pls['FAV']['count_add']} pozycji. Zapisuję...";
        
        // Zrób backup oryginalnej playlisty winampa!
        copy($cfg['dir_winamp'].$cfg['fields']['pls_fav'], $cfg['zip_src'].$cfg['fields']['pls_fav']);
        
        file_put_contents($cfg['dir_winamp'].$cfg['fields']['pls_fav'], implode("\n", $pls['FAV']['items']));
        
        $pls['FAV']['count']        = $pls['FAV']['count_add'];
        $pls['FAV']['count_fix']    = $pls['FAV']['count_add'];
    }
    
    // ========================
    // Opcje: Usuń [Ulubione] z [Kolekcja]
    if($cfg['fields']['is_remfav'])
    {
        $pls['COL']['items'] = array_diff($pls['COL']['items'], $pls['FAV']['items']);
        $pls['COL']['count_sub'] = count($pls['COL']['items']);
        
        $log[] = "Z [{$pls['COL']['title']}] usuwam [{$pls['FAV']['title']}]: {$pls['COL']['count']} - ".($pls['COL']['count'] - $pls['COL']['count_sub'])." = {$pls['COL']['count_sub']} pozycji Zapisuję...";
        
        // Zrób backup oryginalnej playlisty winampa!
        copy($cfg['dir_winamp'].$cfg['fields']['pls_col'], $cfg['zip_src'].$cfg['fields']['pls_col']);
        
        file_put_contents($cfg['dir_winamp'].$cfg['fields']['pls_col'], implode("\n", $pls['COL']['items']));
        
        $pls['COL']['count']        = $pls['COL']['count_sub'];
        $pls['COL']['count_fix']    = $pls['COL']['count_sub'];
    }
    
    
    // ========================
    // Limity ilości utworów
    foreach($pls as $k => $v)
    {
        ork_shuffle($pls[$k]['items']); // wymieszaj wszystkie playlisty na pozniej
        if(!$v['limit']) continue;
        
        $pls[$k]['items'] = array_slice($v['items'], 0, $v['limit']);
        $pls[$k]['count'] = count($pls[$k]['items']);
        $log[] = "Ograniczam [{$v['title']}] do {$pls[$k]['count']} ({$v['count']}) pozycji";
    }
    

    // ========================
    // Utwórz playlistę [Wszystkie] zgodnie z limitem rozmiaru
    // Obetnij resztę pozycji na aktualnej sub-liście
    // ========================
    $totsize = (($cfg['fields']['totsize']>1000) ? round(($cfg['fields']['totsize']/1000)*1024) : $cfg['fields']['totsize']) * pow(2, 20); // to Bytes
    $log[] = 'Łączę playlisty'.($cfg['fields']['is_totsize'] ? ' do rozmiaru '.FormatBytes($totsize) : '').':';

    $all['size']  = 0;
    foreach($pls as $k => $v) 
    {
        $pls[$k]['count_lim'] = 0;
        foreach($v['items'] as $song)
        {
            $size = filesize($song);
            $uid = (float) sprintf('%u', crc32($song));
            
            $isdup = isset($all['items'][$uid]);
            $islim = $all['limit'] && ($all['size'] + $size > $totsize);
            
            // Limit rozmiaru. Uwaga: duplikaty nie zwiększają rozmiaru
            if(!$isdup && $islim) break;
            
            // Licznik pozycji na sub-liście
            // Zalicz nawet jeśli duplikat na [Wszystkie] bo tam nie będzie dodany
            $pls[$k]['count_lim']++;
            
            // Odrzuć duplikaty na [Wszystkie]
            if($isdup) continue;
            
            // Dodaj nowy...
            $all['items'][$uid] = $song;
            $all['size'] += $size;
        }
        
        $pls[$k]['items'] = array_slice($v['items'], 0, $pls[$k]['count_lim']); // Limit rozmiaru!
        $pls[$k]['count'] = $pls[$k]['count_lim'];
        
        $log[] = "\tDodaję {$pls[$k]['count_lim']} ({$v['count_fix']}) pozycji z [{$v['title']}]";
    }
    
    $all['count'] = count($all['items']);
    $log[] = 'Łączny rozmiar '.FormatBytes($all['size'])." w {$all['count']} unikalnych pozycjach";

    $pls['ALL'] = &$all; // Dodaj Super-playlistę do matrixa
    
    
    // ========================
    // Zapisz playlisty
    // ========================
    foreach($pls as $k => $v)
    {
        $log[] = "Zapisuję ".$cfg['fields']['export_'.$v['id']]." playlist [{$v['title']}] = {$v['count']} pozycji";
        
        for($i=1, $j=$cfg['fields']['export_'.$v['id']]; $i <= $j; $i++) {
            ork_shuffle($v['items']);
            $tmp = sprintf('%0'.(floor($j/10)+1).'d', $i);
            ExportPlaylist($cfg['zip_src'].$v['title'].$tmp.'.m3u8', $v['items']);
        }
    }
    
    
    // ========================
    // Dołącz dodatkowe playlisty z Winampa
    // ========================
    $tmp1 = $tmp2 = array();
    if(!empty($cfg['fields']['pls_winamp']))
    {
        $log[] = "Exportuję dodatkowe playlisty z Winampa...";
        
        $tmp = explode(',', $cfg['fields']['pls_winamp']);
        $tmp1 = array_map('trim', $tmp);
    
        // Wczytaj plik XML z informacjami o playlistach ML
        $v = simplexml_load_file($cfg['dir_winamp'].$cfg['pls_winamp']);
        foreach($v->playlist as $pl)
        {
            $k = array_search($pl['title'], $tmp1);
            if($k !== false) {
                file_put_contents($cfg['zip_src'].$pl['title'].'.m3u8', implode("\n", ImportPlaylist($cfg['dir_winamp'].$pl['filename'])));
                $tmp2[$k] = $tmp1[$k];
            }
        }
        // Zapisz log
        $tmp = array();
        foreach($tmp1 as $k => $v) {
            $tmp[] = $tmp2[$k] ? $v : "($v)";
        }
        $k = count($log)-1;
        $log[$k] = $log[$k]." (".count($tmp2)."/".count($tmp1)."): \n\t".implode(",\n\t", $tmp).",";
    }


    // ========================
    // Zapis i sprzatanie
    // ========================
    file_put_contents(
        $cfg['zip_src'].$cfg['log_name'], 
            $cfg['log_intro']
            .implode("\n", $log)."\n\n"
            ."PHP: ".phpversion()." (took ".FormatTime(get_execution_time()).")\n"
            .date('r', time())
    );

    new ZipFolder($cfg['zip_out'], $cfg['zip_src']);
    $log[] = 'Tworzę archiwum: '.realpath($cfg['zip_out']);

    if(is_file($cfg['zip_out'])) {
        $log[] = 'Usuwam katalog roboczy: '.realpath($cfg['zip_src']);
        rrmdir($cfg['zip_src']);
    }

    $t->assign(array(
        'LOG'       => implode("\n", $log),
        'ZIP_FILE'  => $cfg['zip_out'],
    ));
    $t->parse('MAIN.DONE');
}
// ========================
// Wyswietl strone glowna
// ========================
else
{
    foreach($cfg['fields'] as $k => $v)
        $t->assign(strtoupper($k), $v);
    
    foreach(array(
        'is_addtop', 
        'is_remfav', 
        'is_maxtop', 
        'is_maxfav', 
        'is_totsize'
        ) as $k)
        $t->assign(strtoupper($k), $cfg['fields'][$k] ? 'checked="checked"' : '');

    $t->parse('MAIN.DESC');
    $t->parse('MAIN.FORM');
}


// ========================
// Koncowy render
// ========================
$t->parse('MAIN');
$t->out('MAIN');

file_put_contents($cfg['cfg_name'], serialize($cfg));
