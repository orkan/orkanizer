<!-- BEGIN: MAIN --><!DOCTYPE html>
<html lang="en">
<head>
<title>{PHP.title}</title>
<meta charset="utf-8" />
<link href="reset.css" rel="stylesheet" />
<style>
html { 
    background-image: url(../shared/gfx/pat01.png), url(../shared/gfx/bg03a.png);
    background-repeat: repeat-y, repeat;
    background-size: 88px, auto;
    background-position: 860px top, left top;
}
body { margin: 0 2em 3em; font: normal .9em Trebuchet MS, Lucida console; }
strong { font-weight: bold; }
#container { width: 800px; }
#dir_winamp, #pls_winamp { width: 86%; }

h1,h2,h3,h4 { padding: 0.5em 0 .1em; }
h1 { font-size: 300%; }
h2 { font-size: 200%; }
h3 { font-size: 160%; }
h4 { font-size: 120%; }
h4 span { font-size: 80%; }
label { cursor: pointer; }
a, a:hover, a:visited { color: blue; }
a { display: inline-block; line-height: 0.95; text-decoration: none; border-bottom: 1px dashed blue; }
a:hover { border-bottom-style: solid; }

input[type=submit] { padding: .5em; width: 200px; font-weight: bold;  }
input[type="number"] { width:60px; }
.short-numbers input[type="number"] { width:40px; }

.input-fields-horz { margin: 1em 0 0; }
.input-field  {  }
.button-field { margin: 2em 0 0; }
.input-fields-horz .input-field { padding:0 1em; float: left; border-left: 2px solid black; }

.tip { font-style: italic; }
.preloader { padding-right: 18px; background: url(../shared/gfx/preloader02.gif) no-repeat right }
.console { font-family: monospace; }
.error { margin: 2em 0; padding: 2em; text-align:center; border: 5px double red; }
.logger { margin: 1em 0; padding: 1em; white-space: pre-wrap; border: 1px solid gray; }
</style>
<script src="jquery.js"></script>
</head>
<body>
<div id="container">

<!-- BEGIN: ERROR -->
<div class="console error"><h3>Błąd:</h3>{ERRORS}</div>
<!-- END: ERROR -->

<h1>{PHP.title}</h1>


<!-- BEGIN: DESC -->
<pre>* <a href="#" id="tip_count">Przelicz utwory</a>
* <a href="winamp_ml.php">Oczyszczarka Media Library</a>
* Uwaga! Wyłącz Winampa lub zamknij okno Media Library, żeby nadpisać playlisty.
 </pre>
<!-- END: DESC -->

<!-- BEGIN: FORM -->
<form method="POST">

<div class="input-field">
    <h4>Katalog ML Winampa:</h4> 
    <input type="text" id="dir_winamp" name="dir_winamp" value="{DIR_WINAMP}">
</div>

<div class="input-fields-horz">
    <div class="input-field">
        <label for="pls_top"><strong>Najlepsze &gt;&gt; </strong></label>
        <input type="text" name="pls_top" id="pls_top" value="{PLS_TOP}" size="12pt">
    </div>
    <div class="input-field">
        <label for="pls_fav"><strong>Ulubione &gt;&gt; </strong></label>
        <input type="text" name="pls_fav" id="pls_fav" value="{PLS_FAV}" size="12pt">
    </div>
    <div class="input-field">
        <label for="pls_col"><strong>Kolekcja &gt;&gt; </strong></label>
        <input type="text" name="pls_col" id="pls_col" value="{PLS_COL}" size="12pt">
    </div>
    
    <div style="clear:both;"></div>
</div>


<div class="input-fields-horz">
    <h4>Opcje:</h4>
    <div class="input-field">
        <input type="checkbox" name="is_addtop" id="is_addtop" {IS_ADDTOP}>
        <label for="is_addtop" title="">Dodaj <strong>[Najlepsze]</strong> do <strong>[Ulubione]</strong></label>
        <br>
        <input type="checkbox" name="is_remfav" id="is_remfav" {IS_REMFAV}>
        <label for="is_remfav" title="">Usuń <strong>[Ulubione]</strong> z <strong>[Kolekcja]</strong></label>
        <br>
    </div>

    <div style="clear:both;"></div>
</div>


<div class="input-fields-horz">
    <h4>Limity:</h4>

    <div class="input-field">
        <input type="checkbox" name="is_maxtop" id="is_maxtop" {IS_MAXTOP}>
        <label for="is_maxtop">Ogranicz <strong>[Najlepsze]</strong> do:</label>
        <input type="number" name="maxtop" value="{MAXTOP}" size="4"> pozycji
        <br>
        <input type="checkbox" name="is_maxfav" id="is_maxfav" {IS_MAXFAV}>
        <label for="is_maxfav">Ogranicz <strong>[Ulubione]</strong>&nbsp; do:</label>
        <input type="number" name="maxfav" value="{MAXFAV}" size="4"> pozycji
    </div>

    <div class="input-field">
        <input type="checkbox" name="is_totsize" id="is_totsize" {IS_TOTSIZE}>
        <label for="is_totsize">Ogranicz <strong>[Wszystkie]</strong> do:</label>
        <input type="number" name="totsize" value="{TOTSIZE}" size="4"> MB
        <label title="iTunes kompresuje audio do *.mpa i obniża bitrate do 128kbs przed wysłaniem do iPhona (patrz ustawienia). Wielkość końcowa będzie zawsze mniejsza o ok. 20%">(*)</label>
        <br>
        &nbsp;
    </div>

    <div style="clear:both;"></div>
</div>

<div class="input-fields-horz short-numbers">
    <h4>Exportuj playlisty:</h4>
    
    <div class="input-field">
        <label for="export_top"><strong>[Najlepsze]: </strong></label>
        <input type="number" id="export_top" name="export_top" value="{EXPORT_TOP}" size="4">
    </div>
    <div class="input-field">
        <label for="export_fav"><strong>[Ulubione]: </strong></label>
        <input type="number" id="export_fav" name="export_fav" value="{EXPORT_FAV}" size="4">
    </div>
    <div class="input-field">
        <label for="export_col"><strong>[Kolekcja]: </strong></label>
        <input type="number" id="export_col" name="export_col" value="{EXPORT_COL}" size="4">
    </div>
    <div class="input-field">
        <label for="export_all"><strong>[Wszystkie]: </strong></label>
        <input type="number" id="export_all" name="export_all" value="{EXPORT_ALL}" size="4">
    </div>
    
    <div style="clear:both;"></div>
</div>

<div class="input-fields-horz">
    <h4>Exportuj dodatkowe playlisty z Winampa:<span class="tip"> (oddzielaj przecinkami)</span></h4>
    
    <textarea rows="2" id="pls_winamp" name="pls_winamp">{PLS_WINAMP}</textarea> 
</div>

<div class="button-field">
    <input type="submit" value="Generuj!">
</div>


<script type="text/javascript">
    //==========================
    // Global Ajax settings:
    $.ajaxSetup({
        dataType: 'json'
    });
    
    $(function(){
        $('#tip_count').click(function(e) {
            e.preventDefault();
            var $self = $(this);
            $self.addClass('preloader');
            $.get('winamp2itunes.php', {
                m: 'countall', 
                pls_top: $("#pls_top").val(),
                pls_fav: $("#pls_fav").val(),
                pls_col: $("#pls_col").val()
            }, function(json){
                $self.text(json.result);
                $self.removeClass('preloader');
            });
            return 0;
        });
        document.getElementById("is_maxsize{IS_MAXSIZE}").checked = true;
    });
</script>

</form>
<!-- END: FORM -->

<!-- BEGIN: DONE -->
<pre class="console logger">{LOG}</pre>
<a href="{PHP.name}.php">&lt;&lt; Wróć</a>
<iframe src="{ZIP_FILE}" style="display:none"></iframe>
<!-- END: DONE -->

</div>
</body>
</html>
<!-- END: MAIN -->