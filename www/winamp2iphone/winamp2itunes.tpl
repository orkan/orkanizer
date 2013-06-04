<!-- BEGIN: MAIN --><!DOCTYPE html>
<html lang="en">
<head>
<title>{PHP.title}</title>
<meta charset="utf-8" />
<link href="../../../_help/reset.[meyerweb].css" rel="stylesheet" />
<style>
body { margin: 0 2em; font: normal .9em Trebuchet MS, Lucida console; }
#container { width: 1100px; }

h1,h2,h3,h4 { padding: 0.5em 0 .1em; }
h1 { font-size: 300%; }
h2 { font-size: 200%; }
h3 { font-size: 160%; }
h4 { font-size: 120%; }
label { cursor: pointer; }

input[type=submit] { padding: .5em; width: 200px; font-weight:bold;  }

.input-fields-horz {  }
.input-field  {  }
.button-field { margin: 2em 0 0; }
.input-fields-horz .input-field { padding:0 1em; float: left; border-left: 1px solid gray; }

.console { font-family: monospace; }
.error { margin: 2em 0; padding: 2em; text-align:center; border: 5px double red; }
.logger { margin: 1em 0; padding: 1em; border: 1px solid gray; }
</style>
<script src="../../../_jquery/jquery-1.7.1ui-1.8.17/jquery.js"></script>
</head>
<body>
<div id="container">

<!-- BEGIN: ERROR -->
<div class="console error"><h3>Błąd:</h3>{ERRORS}</div>
<!-- END: ERROR -->

<h1>{PHP.title}</h1>
<pre>{PHP.desc}</pre>

<!-- BEGIN: FORM -->
<form method="POST">
<h2>Dodaj playlisty</h2>

<div class="input-field">
    <h4>Ulubione:  (*.m3u8)</h4>
    <input type="text" name="fav_pls" id="fav_pls" value="{FAV_PLS}" size="100%"> &lt; 
    [<a href="#" id="fav_tip" class="tip">plf0001.m3u8</a>]
</div>
<div class="input-field">
    <h4>Pozostale:  (*.m3u8)</h4>
    <input type="text" name="col_pls" id="col_pls" value="{COL_PLS}" size="100%"> &lt; 
    [<a href="#" id="col_tip" class="tip">plf0002.m3u8</a>]
</div>

<div class="input-fields-horz">
    <h4>Wyjście:</h4>
    <div class="input-field">
        <input type="radio" name="is_maxsize" id="is_maxsize0" value="0">
        <label for="is_maxsize0">Ogranicz ilość do:</label>
        <input type="number" name="max_quantity" value="{MAX_QUANTITY}" size="2"> pozycji
    </div>
    <div class="input-field">
        <input type="radio" name="is_maxsize" id="is_maxsize1" value="1">
        <label for="is_maxsize1" title="Pamiętaj, że iTunes może kompresować audio przed wysłaniem do iPhona, więc wielkość końcowa będzie zawsze mniejsza o ok. 20%"
        >Ogranicz wielkość do: *</label>
        <input type="number" name="max_size" value="{MAX_SIZE}" size="2"> MB
    </div>
    <div class="input-field">
        <label>Ulubione playlisty:</label>
        <input type="number" name="fav_files" value="{FAV_FILES}" size="1">
    </div>
    <div class="input-field">
        <label>Wszystkie playlisty:</label>
        <input type="number" name="all_files" value="{ALL_FILES}" size="1">
    </div>
    <div style="clear:both;"></div>
</div>

<div class="button-field">
    <input type="submit" value="Generuj!">
</div>

<script type="text/javascript">
    $(function(){
        $('#fav_tip').click(function() {$("#fav_pls").val("C:\\\Documents and Settings\\\Administrator\\\Application Data\\\Winamp\\\Plugins\\\ml\\\plf0001.m3u8");return 0;});
        $('#col_tip').click(function() {$("#col_pls").val("C:\\\Documents and Settings\\\Administrator\\\Application Data\\\Winamp\\\Plugins\\\ml\\\plf0002.m3u8");return 0;});
    });
    document.getElementById("is_maxsize"+(("{IS_MAXSIZE}")?"1":"0")).checked = true;
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