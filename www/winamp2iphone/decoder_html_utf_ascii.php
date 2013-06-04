<?php
error_reporting(E_ALL ^ E_NOTICE);

$title = 'Dekoder HTML-UTF-ASCII';
$desc = '
 * Konwertuje podany tekst przy użyciu różnych stron kodowych
 * Dodatkowo włączyłem konwertery HTML &lt;&gt; ASCII
';
$name = 'decoder_html_utf_ascii';

require('../shared/php/templates.php');
$t = new XTemplate("{$name}.tpl");

// ========================
// Sprzatanie...
array_map('unlink', glob("{$name}.tmp*.htm"));

// ========================
// [Konwertuj!]
// ========================
if($_SERVER["REQUEST_METHOD"] == 'POST' && !empty($_POST['in']))
{
    $out        = $_POST['in'];
    $enc_from   = $_POST['enc_from'];
    $enc_to     = $_POST['enc_to'];

    if($_POST['urldecode'])                 $out = urldecode($out);
    if($_POST['rawurldecode'])              $out = rawurldecode($out);
    
    if($_POST['html_entity_decode'])        $out = html_entity_decode($out, ENT_NOQUOTES, $enc_from);
    if($_POST['htmlspecialchars_decode'])   $out = htmlspecialchars_decode($out, ENT_QUOTES);

    if($_POST['iconv'])
    {
        $out = str_replace("´", "'", $out); // general replacenemts
        
        $out = str_replace("'", '$?$', $out); // save quotes
        $out = str_replace('"', '$!$', $out); // save double-quotes

        $out = iconv($enc_from, ($_POST['translit'] ? "{$enc_to}//TRANSLIT" : $enc_to), $out);
        $out = str_replace(array("'",'"'), '', $out); // remove quotes added by //TRANSLIT

        $out = str_replace('$!$', '"', $out); // recover double-quotes
        $out = str_replace('$?$', "'", $out); // recover quotes
    }

    if($_POST['htmlentities'])              $out = htmlentities($out, ENT_QUOTES | ENT_DISALLOWED);
    if($_POST['htmlspecialchars'])          $out = htmlspecialchars($out, ENT_NOQUOTES, $enc_from);
    
    if($_POST['urlencode'])                 $out = urlencode($out);
    if($_POST['rawurlencode'])              $out = rawurlencode($out);
    
    $t->assign(array(
        'IN'                        => htmlentities($_POST['in']),
        'HTML_ENTITY_DECODE'        => $_POST['html_entity_decode'] ? 'checked="checked"' : '',
        'HTMLSPECIALCHARS_DECODE'   => $_POST['htmlspecialchars_decode'] ? 'checked="checked"' : '',
        'ENC_FROM'                  => $_POST['enc_from'],
        'ENC_TO'                    => $_POST['enc_to'],
        'ICONV'                     => $_POST['iconv'] ? 'checked="checked"' : '',
        'TRANSLIT'                  => $_POST['translit'] ? 'checked="checked"' : '',
        'HTMLENTITIES'              => $_POST['htmlentities'] ? 'checked="checked"' : '',
        'HTMLSPECIALCHARS'          => $_POST['htmlspecialchars'] ? 'checked="checked"' : '',
        'URLDECODE'                 => $_POST['urldecode'] ? 'checked="checked"' : '',
        'RAWURLDECODE'              => $_POST['rawurldecode'] ? 'checked="checked"' : '',
        'RAWURLENCODE'              => $_POST['rawurlencode'] ? 'checked="checked"' : '',
        'URLENCODE'                 => $_POST['urlencode'] ? 'checked="checked"' : '',
        'OUT'                       => $out,
    ));

    
    // te meta nie dzialaja, trzeba bylo zmieniac nazwe!
    $iframe = '<!DOCTYPE html><html lang="en"><head>
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="content-type" content="text/html; charset='.$_POST['enc_to'].'">
<link href="../../../_help/reset.[meyerweb].css" rel="stylesheet" />
<style>
a { text-decoration: none; }
.info { font: normal .7em \'Lucida Console\',\'Courier New\'; }
.right { float: right; margin-right: 4px; }
</style>
</head><body style="margin: 0, 1em;">
<div class="info"><a class="right" href="javascript:history.go(0)">[refresh]</a>
'.date('r',time()).'</div>
<textarea readonly rows="14" cols="130">'.htmlentities($out).'</textarea>
</body></html>';

    $iframe_file = "{$name}.tmp".uniqid().'.htm';
    file_put_contents($iframe_file, $iframe);
}


// ========================
// Koncowy render
// ========================
$t->assign('IFRAME_FILE', $iframe_file);
$t->parse('MAIN');
$t->out('MAIN');
