<!-- BEGIN: MAIN --><!DOCTYPE html>
<html lang="en">
<head>
<title>{PHP.title}</title>
<meta charset="utf-8" />
<link href="../../../_help/reset.[meyerweb].css" rel="stylesheet" />
<style>
body { margin: 0 2em; font: .9em "Courier New", Courier, monospace; }
#container { width:1100px; }

h1,h2,h3,h4 { padding: 0.5em 0 .1em; font-weight: bold; }
h1 { font-size: 300%; }
h2 { font-size: 200%; }
h3 { font-size: 160%; }
h4 { font-size: 120%; margin-bottom: .5em; }
label { cursor: pointer; }
pre { margin: .5em 0; }

input[type=submit] { padding: .5em; width: 200px; font-weight:bold; }

.input-field { margin: 1em 0; }
.button-field { margin: 2em 0 0; vertical-align: middle; }
.cbox_ident { margin-left: 30px; line-height: 2em; }

.console { font-family: monospace; }
.error { margin: 2em 0; padding: 2em; text-align:center; border: 5px double red; }
.logger { margin: 1em 0; padding: 1em; border: 1px solid gray; }
</style>
<script src="../../../_jquery/jquery-1.7.1ui-1.8.17/jquery.js"></script>
</head>
<body>
<div id="container">

<!-- BEGIN: ERROR -->
<div class="error"><h3>Błąd:</h3>{ERRORS}</div>
<!-- END: ERROR -->

<h1>{PHP.title}</h1>
<pre>{PHP.desc}</pre>


<!-- BEGIN: FORM -->
<form method="POST">

<div class="input-field">
    <h4>Katalog ML Winampa:</h4>
    <input type="text" name="winamp_dir" id="winamp_dir" value="{WINAMP_DIR}" size="100%"> &lt; 
    [<a href="javascript:;" id="winamp_tip" class="tip">domyślne</a>]
</div>

<div class="input-field">
    <h4>Dodatkowe zadania:</h4>
    <input type="checkbox" name="fix_orphaned" id="fix_orphaned" {FIX_ORPHANED}>
    <label for="fix_orphaned" title="">Usuń nieużywane playlisty z ML</label><br>

    <input type="checkbox" name="fix_duplicates" id="fix_duplicates" {FIX_DUPLICATES}>
    <label for="fix_duplicates" title="">Usuń zdublowane utwory w playlistach oraz sortuj</label><br>

    <input type="checkbox" name="fix_sort" id="fix_sort" {FIX_SORT}>
    <label for="fix_sort" title="">Sortuj utwory wg. nazwy pliku</label><br>
    
<!-- 
    <input type="checkbox" name="fix_dead" id="fix_dead" {FIX_DEAD}>
    <label for="fix_dead" title="">Usuń nieaktualne wpisy</label><br>
    
    <input type="checkbox" name="fix_missing" id="fix_missing" {FIX_MISSING}>
    <label for="fix_missing" title="">Szukaj zaginionych utworów w</label>
    <input type="text" name="music_dir" id="music_dir" value="{MUSIC_DIR}" size="57%"><br>
 -->    
    <input type="checkbox" name="fix_path" id="fix_path" {FIX_PATH}>
    <label for="fix_path" title="Najpierw podmień ścieżki w playlistach a potem przenoś pliki - inaczej nie bedzie można zweryfikować plików!">Podmień ścieżki: *</label>
    <div class="cbox_ident" id="fix_path_inputs">
        z: &nbsp;<input type="text" name="fix_path_from" value="{FIX_PATH_FROM}" size="50%"><br>
        na: <input type="text" name="fix_path_to" value="{FIX_PATH_TO}" size="50%">
    </div>

    <div style="clear:both;"></div>
</div>

<div class="button-field">
    <input type="submit" value="Napraw!">
</div>

<script type="text/javascript">
    $(function(){
        $('#winamp_tip').click(function() {$("#winamp_dir").val("C:\\\Documents and Settings\\\Administrator\\\Application Data\\\Winamp\\\Plugins\\\ml")});
    });
</script>
</form>
<!-- END: FORM -->


<!-- BEGIN: DONE -->
<a href="{PHP.name}.php">&lt;&lt; Wróć</a>
<pre class="console logger">{LOG}</pre>
<a href="{PHP.name}.php">&lt;&lt; Wróć</a>
<iframe src="{ZIP_FILE}" style="display:none"></iframe>
<!-- END: DONE -->


</div>
</body>
</html>
<!-- END: MAIN -->