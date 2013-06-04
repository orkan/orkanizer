<?PHP

class XTemplate {

/*
	xtemplate class 0.3pre + MODIFIED FOR LDU & Seditio website engines http://www.neocrome.net / read below
	html generation with templates - fast & easy
	copyright (c) 2000-2001 Barnabás Debreceni [cranx@users.sourceforge.net]

	contributors:
	Ivar Smolin <okul@linux.ee> (14-march-2001)
		- made some code optimizations
	Bert Jandehoop <bert.jandehoop@users.info.wau.nl> (26-june-2001)
		- new feature to substitute template files by other templates
		- new method array_loop()

	latest stable & CVS versions always available @http://sourceforge.net/projects/xtpl/

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public License
	version 2.1 as published by the Free Software Foundation.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details at
	http://www.gnu.org/copyleft/lgpl.html

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/* ====================
Seditio - Website engine
Copyright Neocrome
http://www.neocrome.net
[BEGIN_SED]
File=system/templates.php
Version=102
Updated=2006-apr-17
Type=Core
Author=Neocrome
Description=Templates
[END_SED]

Modifcations made by Olivier C. (http://www.neocrome.net) :

* 10-jun-2003 :
- Function assign modified, for parsing of arrays in arrays.

* 02-feb-2004 :
- Added the function out_to_file
- Commented out the "header(content-length)".
- Commented out the error message if block do not exists.

* 29-may-2006 :
- General cleaning

==================== */

// Variables

var $filecontents = "";				// raw contents of template file
var $blocks = array();				// unparsed blocks
var $parsed_blocks = array();		// parsed blocks
var $preparsed_blocks = array();	// preparsed blocks, for file includes
var $block_parse_order = array();	// block parsing order for recursive parsing
var $sub_blocks = array();			// store sub-block names for fast resetting
var $VARS = array();				// variables array
var $FILEVARS = array();			// file variables array
var $filevar_parent = array();		// filevars' parent block
var $filecache = array();			// file caching
var $tpldir = "";					// location of template files
var $FILES = null;					// file names lookup table
var $file_delim = "/\{FILE\s*\"([^\"]+)\"\s*\}/m"; // regexp for file includes
var $filevar_delim = "/\{FILE\s*\{([A-Za-z0-9\._]+?)\}\s*\}/m"; // regexp for file includes
var $filevar_delim_nl="/^\s*\{FILE\s*\{([A-Za-z0-9\._]+?)\}\s*\}\s*\n/m"; // regexp for file includes w/ newlines
var $block_start_delim="<!-- ";		// block start delimiter
var $block_end_delim="-->";			// block end delimiter
var $block_start_word="BEGIN:";		// block start word
var $block_end_word="END:";			// block end word
var $NULL_STRING = array(""=>"");	// null string for unassigned vars
var $NULL_BLOCK = array(""=>"");	// null string for unassigned blocks
var $mainblock = "main";
var $ERROR = "";
var $AUTORESET = 1;					// auto-reset sub blocks

// Constructor

function XTemplate ($file,$tpldir="", $files=null, $mainblock="main")
	{
	$this->tpldir = $tpldir;

  if (gettype($files)=="array")
    $this->FILES = $files;
	$this->mainblock=$mainblock;
	$this->filecontents=$this->r_getfile($file);	/* read in template file */
	$this->blocks=$this->maketree($this->filecontents,"");	/* preprocess some stuff */
	$this->filevar_parent=$this->store_filevar_parents($this->blocks);
	$this->scan_globals();
}

// Public

function assign ($name, $val="")	
// assign a variable
	{
	if (gettype($name)=="array")
		{
		while (list($k,$v)=each($name))
			{
			if (gettype($k)=="array")
				{
				while (list($k1,$v1)=each($v)) { $this->VARS[$k1]=$v1; }
				}
			else
				{ $this->VARS[$k]=$v; }
			}
		}
	else
		{ $this->VARS[$name]=$val; }
	}

function assign_file ($name, $val="")
// assign a file variable
	{
	if (gettype($name)=="array")
		{
		foreach ($name as $k => $v)
			{ $this->assign_file_($k, $v); }
		}
	else
		{ $this->assign_file_($name, $val); }
	}

function assign_file_ ($name, $val)
	{
	if (isset($this->filevar_parent[$name]))
		{
		if ($val!="")
			{
			$val=$this->r_getfile($val);
			foreach($this->filevar_parent[$name] as $k => $parent)
				{
				if (isset($this->preparsed_blocks[$parent]) and !isset($this->FILEVARS[$name]))
					{ $copy=$this->preparsed_blocks[$parent]; }
				elseif (isset($this->blocks[$parent]))
					{ $copy=$this->blocks[$parent]; }

				preg_match_all($this->filevar_delim,$copy,$res,PREG_SET_ORDER);

				foreach ($res as $h => $v) 
					{
					$copy=preg_replace("/".preg_quote($v[0])."/","$val",$copy);
					$this->preparsed_blocks=array_merge($this->preparsed_blocks,$this->maketree($copy,$parent));
					$this->filevar_parent=array_merge($this->filevar_parent, $this->store_filevar_parents($this->preparsed_blocks));
					}
				}
			}
		}
		$this->FILEVARS[$name]=$val;
	}

function parse ($bname)
// Parse a block
	{
	if (isset($this->preparsed_blocks[$bname]))
		{ $copy=$this->preparsed_blocks[$bname]; }
	elseif (isset($this->blocks[$bname]))
		{ $copy=$this->blocks[$bname]; }
	// else 
		{
		// $this->set_error ("parse: blockname [$bname] does not exist");
		}

	$copy=preg_replace($this->filevar_delim_nl,"",$copy);
	preg_match_all("/\{([A-Za-z0-9\._]+?)}/", $copy, $var_array);
	$var_array=$var_array[1];
	foreach ($var_array as $k => $v)
		{
		$sub=explode(".",$v);
		if ($sub[0]=="_BLOCK_")
			{
			unset($sub[0]);
			$bname2=implode(".",$sub);
			$var=$this->parsed_blocks[$bname2];

			$nul=(!isset($this->NULL_BLOCK[$bname2])) ? $this->NULL_BLOCK[""] : $this->NULL_BLOCK[$bname2];
			if ($var=="")
				{
				if ($nul=="")
					{ $copy=preg_replace("/^\s*\{".$v."\}\s*\n*/m","",$copy); }
				else
					{ $copy=preg_replace("/\{".$v."\}/","$nul",$copy); }
				}
			else
				{
				$var=trim($var);
				$copy=preg_replace("/\{".$v."\}/", "$var", $copy);
				}
			}
		else
			{
			$var=$this->VARS;
			foreach ($sub as $k => $v1)
				{ $var=$var[$v1]; }
			$nul=(!isset($this->NULL_STRING[$v])) ? ($this->NULL_STRING[""]) : ($this->NULL_STRING[$v]);
			$var=(!isset($var))?$nul:$var;
			if ($var=="")
				{ $copy=preg_replace("/^\s*\{".$v."\}\s*\n/m","",$copy); }
			$copy=preg_replace("/\{".$v."\}/","$var",$copy);
			}
		}

	$this->parsed_blocks[$bname].=$copy;

	/* reset sub-blocks */
	if ($this->AUTORESET && (!empty($this->sub_blocks[$bname])))
		{
		reset($this->sub_blocks[$bname]);
		foreach ($this->sub_blocks[$bname] as $k => $v)
			{ $this->reset($v); }
		}
	}

function rparse($bname)
// returns the parsed text for a block, including all sub-blocks.
	{
	if (!empty($this->sub_blocks[$bname]))
		{
		reset($this->sub_blocks[$bname]);
		foreach ($this->sub_blocks[$bname] as $k => $v)
			{
			if (!empty($v))
				{ $this->rparse($v); }
			}
		}
	$this->parse($bname);
	}

function insert_loop($bname,$var,$value="")
// inserts a loop ( call assign & parse )
	{
	$this->assign($var,$value);
	$this->parse($bname);
	}

function array_loop($bname, $var, &$values)
// parses a block for every set of data in the values array
	{
	if (gettype($values)=="array")
  		{
    	foreach($values as $k => $v)
    		{
		    $this->assign($var, $v);
			$this->parse($bname);
			}
		}
	}

function text($bname)
// returns the parsed text for a block
	{
	return $this->parsed_blocks[isset($bname) ? $bname :$this->mainblock];
	}

function out ($bname)
// prints the parsed text
	{
	//	$length=strlen($this->text($bname));
	//	header("Content-Length: ".$length);
	echo $this->text($bname);
	}

function out_to_file ($bname, $fname)
	//prints the parsed text to a specified file
	{
    $fp = @fopen($fname, "wb");
    fwrite($fp, $this->text($bname));
    fclose;
	}

function reset ($bname)
// resets the parsed text
	{
	$this->parsed_blocks[$bname]="";
	}

function parsed ($bname)
// returns true if block was parsed, false if not
	{
	return (!empty($this->parsed_blocks[$bname]));
	}

function SetNullString($str,$varname="")
// sets the string to replace in case the var was not assigned	
	{
	$this->NULL_STRING[$varname]=$str;
	}

function SetNullBlock($str,$bname="")
// sets the string to replace in case the block was not parsed	
	{
	$this->NULL_BLOCK[$bname]=$str;
	}

function set_autoreset()
	{
// sets AUTORESET to 1. (default is 1)
// if set to 1, parse() automatically resets the parsed blocks' sub blocks
// (for multiple level blocks)	
	$this->AUTORESET=1;
	}

function clear_autoreset()
// sets AUTORESET to 0. (default is 1)
// if set to 1, parse() automatically resets the parsed blocks' sub blocks
// (for multiple level blocks)
	{
 	$this->AUTORESET=0;
	}

function scan_globals()
// scans global variables
	{
	reset($GLOBALS);
	foreach ($GLOBALS as $k => $v)
		{ $GLOB[$k]=$v; }
	$this->assign("PHP",$GLOB);
	}

// Private

function maketree($con,$parentblock="")
// generates the array containing to-be-parsed stuff:
// $blocks["main"],$blocks["main.table"],$blocks["main.table.row"], etc.
// also builds the reverse parse order.
 	{
 	$blocks = array();
	$con2 = explode($this->block_start_delim, $con);
	if (!empty($parentblock))
		{
		$block_names = explode(".",$parentblock);
		$level = sizeof($block_names);
		}
	else
		{
		$block_names = array();
		$level = 0;
		}
	foreach ($con2 as $k => $v)
		{
		$patt="($this->block_start_word|$this->block_end_word)\s*(\w+)\s*$this->block_end_delim(.*)";
		if (preg_match_all("/$patt/ims",$v,$res,PREG_SET_ORDER)) {
			// $res[0][1] = BEGIN or END, $res[0][2] = block name, $res[0][3] = kinda content
			if ($res[0][1]==$this->block_start_word)
				{
				$parent_name=implode(".",$block_names);
				$block_names[++$level]=$res[0][2];			// add one level - array("main","table","row")
				$cur_block_name=implode(".",$block_names);	// make block name (main.table.row)
				$this->block_parse_order[]=$cur_block_name;	// build block parsing order (reverse)
				$blocks[$cur_block_name].=$res[0][3];		// add contents
				$blocks[$parent_name].="{_BLOCK_.$cur_block_name}";	// add {_BLOCK_.blockname} string to parent block
				$this->sub_blocks[$parent_name][]=$cur_block_name;	// store sub block names for autoresetting and recursive parsing
				$this->sub_blocks[$cur_block_name][]="";			// store sub block names for autoresetting */
				}
			elseif ($res[0][1]==$this->block_end_word)
				{
				unset($block_names[$level--]);
				$parent_name=implode(".",$block_names);
				$blocks[$parent_name].=$res[0][3];	// add rest of block to parent block
  				}
			}
		else
			{ // no block delimiters found
			if ($k)
				{ $blocks[implode(".",$block_names)].=$this->block_start_delim; }
			$blocks[implode(".",$block_names)].=$v;
			}
		}
	return $blocks;
	}

function store_filevar_parents($blocks)
// store container block's name for file variables
	{
	$parents=array();
	foreach ($blocks as $bname => $con)
		{
		preg_match_all($this->filevar_delim,$con,$res);
		foreach ($res[1] as $k => $v)
			{ $parents[$v][]=$bname; }
		}
	return $parents;
	}

function get_error()
// sets and gets error
	{
	return ($this->ERROR=="")?0:$this->ERROR;
	}


function set_error($str)
	{
	$this->ERROR="<b>[XTemplate]</b>&nbsp;<i>".$str."</i>";
	trigger_error($this->get_error());
	}

function getfile($file)
	{
	if (!isset($file))
		{
		$this->set_error("!isset file name!");
		return "";
		}

	if (isset($this->FILES))
  		{
    	if (isset($this->FILES[$file]))
      		{ $file = $this->FILES[$file]; }
 		}

	if (!empty($this->tpldir))
  		{ $file = $this->tpldir."/".$file; }

	if (isset($this->filecache[$file]))
		{
		$file_text=$this->filecache[$file];
		}
	else
		{
		if (is_file($file))
			{
			/*
			if (!($fh=fopen($file,"r")))
				{
				$this->set_error("Cannot open file: $file");
				return "";
				}

			$file_text=fread($fh,filesize($file));
			fclose($fh);
			*/

			if (!($file_text=file_get_contents($file)))
				{
				$this->set_error("Cannot open file: $file");
				return "";
				}
			}
		else
			{
			$this->set_error("[$file] does not exist");
			$file_text="<b>__XTemplate fatal error: file [$file] does not exist__</b>";
			}	
		$this->filecache[$file]=$file_text;
		}
	return $file_text;
	}

function r_getfile($file)
// recursively gets the content of a file with {FILE "filename.tpl"} directives
	{
	$text=$this->getfile($file);
	while (preg_match($this->file_delim,$text,$res))
		{
		$text2=$this->getfile($res[1]);
		$text=preg_replace("'".preg_quote($res[0])."'",$text2,$text);
		}
	return $text;
	}
	
} // End of the class

?>
