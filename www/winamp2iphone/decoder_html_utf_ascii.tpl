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

input[type=submit] { padding: .5em; width: 200px; height: 6em; font-weight:bold; }

.fields-table { display: table; margin: 1em 0; line-height: 1.7em;  }
.fields-table-row { display: table-row; }
.fields-table-cell { display: table-cell; padding: 0 1em; border-left: 1px solid gray; }

.cell-header,
.cell-header-ins { font: bold 0.7em 'Lucida Console'; text-indent: 4px; }
.cell-header-ins { text-align: center; }

.button-field { margin: 2em 0 0; vertical-align: middle; }
.error { margin: 2em 0; padding: 2em; text-align:center; border: 5px double red; }
</style>
</head>
<body>
<div id="container">

<!-- BEGIN: ERROR -->
<div class="error"><h3>Błąd:</h3>{ERRORS}</div>
<!-- END: ERROR -->

<h1>{PHP.title}</h1>
<pre>{PHP.desc}</pre>

<form method="POST">

<div class="input-field">
    <h4>Wejście: </h4>
    <textarea name="in" cols="130" rows="14">{IN}</textarea>
</div>

<div class="fields-table">
    <div class="fields-table-row">


        <div class="fields-table-cell button-field">
            <input type="submit" value="Dekoduj!">
        </div>

        <div class="fields-table-cell">
            <div class="cell-header">Groupa 1</div>
            <input type="checkbox" name="html_entity_decode" id="html_entity_decode" {HTML_ENTITY_DECODE}>
            <label for="html_entity_decode" title="Convert all HTML entities to their applicable characters. Use htmlentities() for reverse.">html_entity_decode([Wejście], ENT_NOQUOTES, 'UTF-8');</label><br>
            <input type="checkbox" name="htmlspecialchars_decode" id="htmlspecialchars_decode" {HTMLSPECIALCHARS_DECODE}>
            <label for="htmlspecialchars_decode" title="Convert special HTML entities back to characters. Use htmlspecialchars() for reverse.">htmlspecialchars_decode([Wejście], ENT_QUOTES);</label><br>

            <input type="checkbox" name="iconv" id="iconv" {ICONV}>
            <label for="iconv" title="Convert string to requested character encoding">iconv(</label>
            <select name="enc_from" id="enc_from">
                <option value="UTF-8" selected>UTF-8</option>
                <option value="ASCII">ASCII</option>
                <option value="ISO-8859-1">ISO-8859-1</option>
                <option value="ISO-8859-2">ISO-8859-2</option>
                <option value="CP1250">CP1250</option>
                <option value="CP850">CP850</option>
                <option value="CP866">CP866</option>
            </select>
            ,
            <select name="enc_to" id="enc_to">
                <option value="UTF-8">UTF-8</option>
                <option value="ASCII" selected>ASCII</option>
                <option value="ISO-8859-1">ISO-8859-1</option>
                <option value="ISO-8859-2">ISO-8859-2</option>
                <option value="CP1250">CP1250</option>
                <option value="CP850">CP850</option>
                <option value="CP866">CP866</option>
            </select>
            <input type="checkbox" name="translit" id="translit" {TRANSLIT}> <label for="translit">//TRANSLIT</label>
            , [Wejście]);<br>

            <input type="checkbox" name="htmlentities" id="htmlentities" {HTMLENTITIES}>
            <label for="htmlentities" title="Convert all applicable characters to HTML entities. Use html_entity_decode() for reverse.">htmlentities([Wejście], ENT_QUOTES | ENT_DISALLOWED);</label><br>
            <input type="checkbox" name="htmlspecialchars" id="htmlspecialchars" {HTMLSPECIALCHARS}>
            <label for="htmlspecialchars" title="Convert special characters to HTML entities. Use htmlspecialchars_decode() for reverse.">htmlspecialchars([Wejście], ENT_QUOTES, 'UTF-8');</label><br>
        </div>

        <div class="fields-table-cell">
            <div class="cell-header">Groupa 2</div>
            <input type="checkbox" name="rawurldecode" id="rawurldecode" {RAWURLDECODE}>
            <label for="rawurldecode" title="Returns a string in which the sequences with percent (%) signs followed by two hex digits have been replaced with literal characters.">rawurldecode([Wejście]);</label><br>
            <input type="checkbox" name="urldecode" id="urldecode" {URLDECODE}>
            <label for="urldecode" title="Decodes any %## encoding in the given string. Plus symbols ('+') are decoded to a space character.">urldecode([Wejście]);</label><br>
            <div class="cell-header-ins" >-=[Groupa 1]=-</div>
            <input type="checkbox" name="urlencode" id="urlencode" {URLENCODE}>
            <label for="urlencode" title="This function is convenient when encoding a string to be used in a query part of a URL, as a convenient way to pass variables to the next page.">urlencode([Wejście]);</label><br>
            <input type="checkbox" name="rawurlencode" id="rawurlencode" {RAWURLENCODE}>
            <label for="rawurlencode" title="Returns a string in which all non-alphanumeric characters except -_.~ have been replaced with a percent (%) sign followed by two hex digits. See RFC 3986">rawurlencode([Wejście]);</label><br>
        </div>


    </div>
    <div style="clear:both;"></div>
</div>

<div class="input-field">
    <h4>Wyjście [iframe]:</h4>
    <iframe src="{IFRAME_FILE}" width="1070" height="315"></iframe>
</div>

<script type="text/javascript">
    // select: enc_from
    var el = document.getElementById("enc_from"), found = 0;
    for(var i=0; i<el.length; i++) if(el.options[i].value=="{ENC_FROM}") {found++; break;}
    if(found) el.options.selectedIndex = i;
    // select: enc_to
    el = document.getElementById("enc_to"), found = 0;
    for(var i=0; i<el.length; i++) if(el.options[i].value=="{ENC_TO}") {found++; break;}
    if(found) el.options.selectedIndex = i;
</script>
</form>


</div>
</body>
</html>
<!-- END: MAIN -->