<?php 
// WC-forecast.php script by Ken True - webmaster@saratoga-weather.org
//  Based on WU-forecast.php V3.05 - 13-Jun-2018 and rewritten to use the new WeatherUnderground api.weather.com
//    json data 5 day forecast to replace the deprecated weatherunderground.com API which
//    was turned off in March, 2019.
//
// Version 1.00 - 28-Feb-2019 - initial release
// Version 1.01 - 02-Mar-2019 - corrected some WU variable names to WC variable names
// Version 1.02 - 04-Mar-2019 - rewrote icon printing for clairity and Day-over-Night display option
// Version 1.03 - 10-Mar-2019 - added displays for all available icons in day-over-night mode
// Version 1.04 - 22-Mar-2019 - fix TWC/WU JSON degree sign \xc2\xba to correct UTF-8 \xc2\xb0
//
$Version = "WC-forecast.php (ML) Version 1.04 - 22-Mar-2019";
//
// error_reporting(E_ALL);  // uncomment to turn on full error reporting
//
// script available at http://saratoga-weather.org/script-WCforecast.php
//  
// you may copy/modify/use this script as you see fit,
// no warranty is expressed or implied.
//
// This script parses the Weather Channel 5-day forecast JSON API and loads icons/text into
//  arrays so you can use them in your weather website.  
//
// NOTE: You must leave an attribution link to weatherunderground.com in the output page.
//
// output: creates XHTML 1.0-Strict HTML page (or inclusion)
//
//  You can also invoke options directly in PHP like this
//
//    $doIncludeWC = true;
//    include("WC-forecast.php");  for just the text
//  or ------------
//    $doPrintWC = false;
//    include("WC-forecast.php");  for setting up the $WCforecast... variables without printing
//
//  or ------------
//    $doIncludeWC = true;
//    $doPrintHeadingWC = true;
//    $doPrintIconsWC = true;
//    $doPrintTextWC = false
//    include("WC-forecast.php");  include mode, print only heading and icon set
//
// Variables returned (useful for printing an icon or forecast or two...)
//
// $WCforecastcity 		- Name of city from WC Forecast header
//
// The following variables exist for $i=0 to $i= number of forecast periods minus 1
//  a loop of for ($i=0;$i<count($WCforecastday);$i++) { ... } will loop over the available 
//  values.
//
// $WCforecastday[$i]	- period of forecast
// $WCforecasttext[$i]	- text of forecast 
// $WCforecasttemp[$i]	- Temperature with text and formatting
// $WCforecastpop[$i]	- Number - Probabability of Precipitation ('',10,20, ... ,100)
// $WCforecasticon[$i]   - base name of icon graphic to use
// $WCforecastcond[$i]   - Short legend for forecast icon 
// $WCforecasticons[$i]  - Full icon with Period, <img> and Short legend.
// $WCforecastthunder[$i] - thunder risk (text)
// $WCforecastuv[$i] - formatted UVIndex and word description
// $WCforecastsnow[$i] - amount of snow forecast (or empty if no snow)
// $WCforecastrain[$i] - amount of rain forecast (or empty if no rain)
// 
//
// Settings ---------------------------------------------------------------
//REQUIRED: a WU API KEY.. sign up at https://www.wunderground.com/member/api-keys
$WCAPIkey = 'specify-for-standalone-use-here'; // use this only for standalone / non-template use
// NOTE: if using the Saratoga template, add to Settings.php a line with:
//    $SITE['WCAPIkey'] = 'your-api-key-here';
// and that will enable the script to operate correctly in your template
//
// Select which units will be used for the displays:
//
//$WCunits  = 'e';  // 'e'= US units F,mph,inHg,in,in
$WCunits  = 'm';  // 'm'= metric   C,km/h,hPa,mm,cm
//$WCunits  = 'h';  // 'h'= UK units C,mph,mb,mm,cm
//$WCunits  = 's';  // 's'= SI units C,m/s,hPa,mm,cm
//
$iconDir ='./forecast/images/';	// directory for carterlake icons './forecast/images/'
$iconType = '.jpg'; // default type='.jpg' 
//                     use '.gif' for animated icons from http://www.meteotreviglio.com/
//
//
$WC_LOC = 'Saratoga, CA, USA|37.27465,-122.02295';
//
// The optional multi-city forecast .. make sure the first entry is for the $WC_LOC location
// The contents will be replaced by $SITE['WCforecasts'] if specified in your Settings.php
//*

$WCforecasts = array(
 // Location name to display|lat,long  (separated by | character)
'Saratoga, CA, USA|37.27465,-122.02295',
'Auckland, NZ|-36.910,174.771', // Awhitu, Waiuku New Zealand
'Assen, NL|53.02277,6.59037',
'Blankenburg, DE|51.8089941,10.9080649',
'Cheyenne, WY, USA|41.144259,-104.83497',
'Carcassonne, FR|43.2077801,2.2790407',
'Braniewo, PL|54.3793635,19.7853585',
'Omaha, NE, USA|41.19043,-96.13114',
'Johanngeorgenstadt, DE|50.439339,12.706085',
'Athens, GR|37.97830,23.715363',
'Haifa, IL|32.7996029,34.9467358',
'Tahoe Vista, CA, USA|39.2403,-120.0528',
'Auburn, CA, USA|38.8962,-121.0789',
); 
//*/
$commaDecimal = false;                 // set to true to process numbers with a comma for a decimal point
//
$maxWidth = '640px';                   // max width of tables (could be '100%')
$maxForecastLegendWords = 4;           // more words in forecast legend than this number will use our forecast words 
$autoSetTemplate = true;               // =true; set icons based on wide/narrow template design
//                                     // =false; don't autoset maxWidth based on Saratoga wide/narrow 
$foldIconRow = true;                   // =true; display icons in rows of 5 if long texts are found
$iconRowDayNight = true;               // =false; 9 icons in a row, folded over if long texts.
//                                     // =true;  icons always in two rows Day over Night
$cacheFileDir = './';                  // default cache file directory
$cacheName = "WC-forecast-json.txt";   // locally cached page from WC
$refetchSeconds = 3600;                // cache lifetime (3600sec = 60 minutes)
$charsetOutput = 'ISO-8859-1';         // default character encoding of output ='ISO-8859-1' for Saratoga templates
$lang = 'en';                          // default language ='en' for English
// ---- end of settings ---------------------------------------------------

// overrides from Settings.php if available
global $SITE;
if (isset($SITE['WCforecasts']))   {$WCforecasts = $SITE['WCforecasts']; }
if (isset($SITE['WCAPIkey']))      {$WCAPIkey = $SITE['WCAPIkey']; } 
if (isset($SITE['WCunits']))       {$WCunits = $SITE['WCunits']; } 
if (isset($SITE['fcsturlWC']))     {$WC_LOC = $SITE['fcsturlWC'];}
if (isset($SITE['fcsticonsdir']))  {$iconDir = $SITE['fcsticonsdir'];}
if (isset($SITE['fcsticonstype'])) {$iconType = $SITE['fcsticonstype'];}
if (isset($SITE['charset']))       {$charsetOutput = strtoupper($SITE['charset']); }
if (isset($SITE['lang']))          {$lang = $SITE['lang'];}
if(isset($SITE['cacheFileDir']))   {$cacheFileDir = $SITE['cacheFileDir']; }
if(isset($SITE['foldIconRow']))    {$foldIconRow = $SITE['foldIconRow']; }
if(isset($SITE['iconRowDayNight'])) {$iconRowDayNight = $SITE['iconRowDayNight']; }
if(isset($SITE['RTL-LANG']))       {$RTLlang = $SITE['RTL-LANG']; }
if (isset($SITE['commaDecimal']))  {$commaDecimal = $SITE['commaDecimal'];}
// end of overrides from Settings.php
//
// -------------------begin code ------------------------------------------


if (isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
   //--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain;charset=ISO-8859-1");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   
   readfile($filenameReal);
   exit;
}

$Status = "<!-- $Version on PHP ".phpversion()." -->\n";
//------------------------------------------------

if(preg_match('|specify|i',$WCAPIkey)) {
	print "<p>Note: the WC-forecast.php script requires an API key from WeatherUnderground to operate.<br/>";
	print "Visit <a href=\"https://www.wunderground.com/member/api-keys\">Weather Underground</a> to ";
	print "register for an API key. You must have a PWS submitting data to WU to acquire an API key.</p>\n";
	if( isset($SITE['fcsturlWC']) ) {
		print "<p>Insert in Settings.php an entry for:<br/><br/>\n";
		print "\$SITE['WCAPIkey'] = '<i>your-key-here</i>';<br/><br/>\n";
		print "replacing <i>your-key-here</i> with your WU/TWC API key.</p>\n";
	}
	return;
}

$NWSiconlist = array( // translation from api.weather.gov icons to NWS-styled icons Day,Night
  0 => array('tor.jpg','ntor.jpg'), // Tornado, 00.png, Forecast, Night + Day
  1 => array('tropstorm-noh.jpg','tropstorm-noh.jpg'), // Tropical Storm, 01.png, Forecast + Observations, Night + Day
  2 => array('hurr-noh.jpg','hurr-noh.jpg'), // Hurricane, 02.png, Forecast, Night + Day
  3 => array('scttsra.jpg','nscttsra.jpg'), // Strong Storms, 03.png, Forecast, Night + Day
  4 => array('tsra.jpg','ntsra.jpg'), // Thunder and Hail, 04.png, Forecast + Observations, Night + Day
  5 => array('rasn.jpg','nrasn.jpg'), // Rain to Snow Showers, 05.png, Forecast + Observations, Night + Day
  6 => array('raip.jpg','nraip.jpg'), // Rain / Sleet, 06.png, Forecast + Observations, Night + Day
  7 => array('mix.jpg','nmix.jpg'), // Wintry Mix Snow / Sleet, 07.png, Forecast + Observations, Night + Day
  8 => array('fzrara.jpg','fzrara.jpg'), // Freezing Drizzle, 08.png, Forecast + Observations, Night + Day
  9 => array('mist.jpg','mist.jpg'), // Drizzle, 09.png, Forecast + Observations, Night + Day
  10 => array('fzra.jpg','nfzra.jpg'), // Freezing Rain, 10.png, Forecast + Observations, Night + Day
  11 => array('ra.jpg','nra.jpg'), // Light Rain, 11.png, Forecast + Observations, Night + Day
  12 => array('ra.jpg','nra.jpg'), // Rain, 12.png, Forecast + Observations, Night + Day
  13 => array('sn.jpg','nsn.jpg'), // Scattered Flurries, 13.png, Forecast + Observations, Night + Day
  14 => array('sn.jpg','nsn.jpg'), // Light Snow, 14.png, Forecast + Observations, Night + Day
  15 => array('blizzard.jpg','nblizzard.jpg'), // Blowing / Drifting Snow, 15.png, Forecast + Observations, Night + Day
  16 => array('sn.jpg','nsn.jpg'), // Snow, 16.png, Forecast + Observations, Night + Day
  17 => array('ip.jpg','nip.jpg'), // Hail, 17.png, Forecast + Observations, Night + Day
  18 => array('ip.jpg','nip.jpg'), // Sleet, 18.png, Forecast + Observations, Night + Day
  19 => array('du.jpg','ndu.jpg'), // Blowing Dust / Sandstorm, 19.png, Forecast + Observations, Night + Day
  20 => array('fg.jpg','nfg.jpg'), // Foggy, 20.png, Forecast + Observations, Night + Day
  21 => array('hazy.jpg','hazy.jpg'), // Haze / Windy, 21.png, Forecast + Observations, Night + Day
  22 => array('fu.jpg','nfu.jpg'), // Smoke / Windy, 22.png, Forecast + Observations, Night + Day
  23 => array('wind.jpg','nwind.jpg'), // Breezy, 23.png, Forecast, Night + Day
  24 => array('wind.jpg','nwind.jpg'), // Blowing Spray / Windy, 24.png, Forecast + Observations, Night + Day
  25 => array('cold.jpg','ncold.jpg'), // Frigid / Ice Crystals, 25.png, Forecast + Observations, Night + Day
  26 => array('ovc.jpg','novc.jpg'), // Cloudy, 26.png, Forecast + Observations, Night + Day
  27 => array('bkn.jpg','nbkn.jpg'), // Mostly Cloudy, 27.png, Forecast + Observations, Night + Day
  28 => array('bkn.jpg','nbkn.jpg'), // Mostly Cloudy, 28.png, Forecast + Observations, Day
  29 => array('sct.jpg','nsct.jpg'), // Partly Cloudy, 29.png, Forecast + Observations, Night
  30 => array('sct.jpg','nsct.jpg'), // Partly Cloudy, 30.png, Forecast + Observations, Day
  31 => array('skc.jpg','nskc.jpg'), // Clear, 31.png, Forecast + Observations, Night
  32 => array('skc.jpg','nskc.jpg'), // Sunny, 32.png, Forecast + Observations, Day
  33 => array('few.jpg','nfew.jpg'), // Fair / Mostly Clear, 33.png, Forecast + Observations, Night
  34 => array('few.jpg','nfew.jpg'), // Fair / Mostly Sunny, 34.png, Forecast + Observations, Day
  35 => array('raip.jpg','nraip.jpg'), // Mixed Rain & Hail, 35.png, Forecast, Day
  36 => array('hot.jpg','hot.jpg'), // Hot, 36.png, Forecast, Day
  37 => array('scttsra.jpg','nscttsra.jpg'), // Isolated Thunderstorms, 37.png, Forecast, Day
  38 => array('tsra.jpg','ntsra.jpg'), // Thunderstorms, 38.png, Forecast + Observations, Night + Day
  39 => array('hi_shwrs.jpg','hi_nshwrs.jpg'), // Scattered Showers, 39.png, Forecast, Day
  40 => array('shra.jpg','nshra.jpg'), // Heavy Rain, 40.png, Forecast + Observations, Night + Day
  41 => array('sn.jpg','nsn.jpg'), // Scattered Snow Showers, 41.png, Forecast, Day
  42 => array('sn.jpg','nsn.jpg'), // Heavy Snow, 42.png, Forecast + Observations, Night + Day
  43 => array('blizzard.jpg','nblizzard.jpg'), // Blizzard, 43.png, Forecast, Night + Day
  44 => array('na.jpg','na.jpg'), // Not Available (N/A), 44.png, Forecast, Night + Day
  45 => array('hi_shwrs.jpg','hi_nshwrs.jpg'), // Scattered Showers, 45.png, Forecast, Night
  46 => array('sn.jpg','nsn.jpg'), // Scattered Snow Showers, 46.png, Forecast, Night
  47 => array('scttsra.jpg','nscttsra.jpg'), // Scattered Thunderstorms, 47.png, Forecast + Observations, Night + Day
) ;
//*/

if(!function_exists('langtransstr')) {
	// shim function if not running in template set
	function langtransstr($input) { return($input); }
}

if(!function_exists('json_last_error')) {
	// shim function if not running PHP 5.3+
	function json_last_error() { return('- N/A'); }
	$Status .= "<!-- php V".phpversion()." json_last_error() stub defined -->\n";
	if(!defined('JSON_ERROR_NONE')) { define('JSON_ERROR_NONE',0); }
	if(!defined('JSON_ERROR_DEPTH')) { define('JSON_ERROR_DEPTH',1); }
	if(!defined('JSON_ERROR_STATE_MISMATCH')) { define('JSON_ERROR_STATE_MISMATCH',2); }
	if(!defined('JSON_ERROR_CTRL_CHAR')) { define('JSON_ERROR_CTRL_CHAR',3); }
	if(!defined('JSON_ERROR_SYNTAX')) { define('JSON_ERROR_SYNTAX',4); }
	if(!defined('JSON_ERROR_UTF8')) { define('JSON_ERROR_UTF8',5); }
}

WC_loadLangDefaults (); // set up the language defaults

$WCLANG = 'en-US'; // Default to English for API
$RTLlang = ',he,jp,cn,';  // languages that use right-to-left order

$lang = strtolower($lang); 	
if(isset($WClanguages[$lang]) ) { // if $lang is specified, use it
	$SITE['lang'] = $lang;
	$WCLANG = $WClanguages[$lang];
	if($charsetOutput !== 'UTF-8') {
	  $charsetOutput = (isset($WClangCharsets[$lang]))?$WClangCharsets[$lang]:$charsetOutput;
	}
}

if(isset($_GET['lang']) and isset($WClanguages[strtolower($_GET['lang'])]) ) { // template override
	$lang = strtolower($_GET['lang']);
	$SITE['lang'] = $lang;
	$WCLANG = $WClanguages[$lang];
	if($charsetOutput !== 'UTF-8') {
	  $charsetOutput = (isset($WClangCharsets[$lang]))?$WClangCharsets[$lang]:$charsetOutput;
	}
}

$doRTL = (strpos($RTLlang,$lang) !== false)?true:false;  // format RTL language in Right-to-left in output

// get the selected forecast location code
$haveIndex = '0';
if (!empty($_GET['z']) && preg_match("/^[0-9]+$/i", htmlspecialchars($_GET['z']))) {
  $haveIndex = htmlspecialchars(strip_tags($_GET['z']));  // valid zone syntax from input
} 

if(!isset($WCforecasts[0])) {
	// print "<!-- making NWSforecasts array default -->\n";
	$WCforecasts = array("$WC_LOC"); // create default entry
}

//  print "<!-- NWSforecasts\n".print_r($WCforecasts,true). " -->\n";
// Set the default zone. The first entry in the $SITE['NWSforecasts'] array.
list($Nl,$Nn) = explode('|',$WCforecasts[0].'|||');
$FCSTlocation = $Nl;
$WC_LOC = $Nn;

if(!isset($WCforecasts[$haveIndex])) {
	$haveIndex = 0;
}

// locations added to the drop down menu and set selected zone values
$dDownMenu = '';
for ($m=0;$m<count($WCforecasts);$m++) { // for each locations
  list($Nlocation,$Nname) = explode('|',$WCforecasts[$m].'|||');
  $seltext = '';
  if($haveIndex == $m) {
    $FCSTlocation = $Nlocation;
    $WC_LOC = $Nname;
	$seltext = ' selected="selected" ';
  }
  $dDownMenu .= "     <option value=\"$m\"$seltext>".langtransstr($Nlocation)."</option>\n";
}

// build the drop down menu
$ddMenu = '';
// create menu if at least two locations are listed in the array
if (isset($WCforecasts[0]) and isset($WCforecasts[1])) {
	if($doRTL) {$RTLopt = ' style="direction: rtl;"'; } else {$RTLopt = '';}; 
	$ddMenu .= '<tr align="center">
      <td style="font-size: 14px; font-family: Arial, Helvetica, sans-serif">
      <script type="text/javascript">
        <!--
        function menu_goto( menuform ){
         selecteditem = menuform.logfile.selectedIndex ;
         logfile = menuform.logfile.options[ selecteditem ].value ;
         if (logfile.length != 0) {
          location.href = logfile ;
         }
        }
        //-->
      </script>
     <form action="" method="get">
     <p><select name="z" onchange="this.form.submit()"'.$RTLopt.'>
     <option value=""> - '.langtransstr('Select Forecast').' - </option>
' . $dDownMenu .
		$ddMenu . '     </select></p>
     <div><noscript><pre><input name="submit" type="submit" value="'.langtransstr('Get Forecast').'" /></pre></noscript></div>
     </form>
    </td>
   </tr>
';
}

$Force = false;
if (isset($_REQUEST['force']) and  $_REQUEST['force']=="1" ) {
  $Force = true;
}

$doDebug = false;
if (isset($_REQUEST['debug']) and strtolower($_REQUEST['debug'])=='y' ) {
  $doDebug = true;
}

$fileName = $WC_LOC;
if ($doDebug) {
  $Status .= "<!-- WC LOC: $fileName -->\n";
}

if ($autoSetTemplate and isset($_SESSION['CSSwidescreen'])) {
	if($_SESSION['CSSwidescreen'] == true) {
	   $maxWidth = '900px';
	   $Status .= "<!-- autoSetTemplate using ".$SITE['CSSwideOrNarrowDefault']." aspect. -->\n";	
	}
	if($_SESSION['CSSwidescreen'] == false) {
	   $maxWidth = '640px';
	   $Status .= "<!-- autoSetTemplate using ".$SITE['CSSwideOrNarrowDefault']." aspect. -->\n";	
	}
}

$cacheName = $cacheFileDir . $cacheName;
// unique cache per index:UOM:language used
$cacheName = preg_replace('|\.txt|is',"-$haveIndex-$WCunits-$lang.txt",$cacheName); 

$APIfileName = WC_get_APIURL($fileName); // transform WC page URL to API query URL

if (! $Force and file_exists($cacheName) and filemtime($cacheName) + $refetchSeconds > time()) {
      $html = implode('', file($cacheName)); 
      $Status .= "<!-- loading from $cacheName (" . strlen($html) . " bytes) -->\n"; 
  } else { 
      $Status .= "<!-- loading from $APIfileName. -->\n"; 
      $html = WC_fetchUrlWithoutHanging($APIfileName,false); 
	  
    $RC = '';
	if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$html,$matches)) {
	    $RC = trim($matches[1]);
	}
	$Status .= "<!-- RC=$RC, bytes=" . strlen($html) . " -->\n";
	if (preg_match('|30\d|',$RC)) { // handle possible blocked redirect
	   preg_match('|Location: (\S+)|is',$html,$matches);
	   if(isset($matches[1])) {
		  $sURL = $matches[1];
		  if(preg_match('|opendns.com|i',$sURL)) {
			  $Status .= "<!--  NOT following to $sURL --->\n";
		  } else {
			$Status .= "<!-- following to $sURL --->\n";
		
			$html = WC_fetchUrlWithoutHanging($sURL,false);
			$RC = '';
			if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$html,$matches)) {
				$RC = trim($matches[1]);
			}
			$Status .= "<!-- RC=$RC, bytes=" . strlen($html) . " -->\n";
		  }
	   }
    }
    if(preg_match('|daypart|Uis',$html)) {
      $fp = fopen($cacheName, "w"); 
      if (!$fp) { 
        $Status .= "<!-- unable to open $cacheName for writing. -->\n"; 
      } else {
        $write = fputs($fp, $html); 
        fclose($fp);  
        $Status .= "<!-- saved cache to $cacheName (". strlen($html) . " bytes) -->\n";
      }
    } elseif (file_exists($cacheName)) {
      $html = implode('', file($cacheName));
      $Status .= "<!-- WC API return has no forecast .. reusing existing cache file -->\n"; 
      $Status .= "<!-- loading from $cacheName (" . strlen($html) . " bytes) -->\n"; 
    }
} 

// establish JSON charset.  Usually in UTF-8
  preg_match('|charset="{0,1}(\S+)"{0,1}|i',$html,$matches);
  if (isset($matches[1])) {
    $charsetInput = strtoupper($matches[1]);
  } else {
    $charsetInput = 'UTF-8';
  }
  
 $doIconv = ($charsetInput == $charsetOutput)?false:true; // only do iconv() if sets are different
 
 $Status .= "<!-- using charsetInput='$charsetInput' charsetOutput='$charsetOutput' doIconv='$doIconv' doRTL='$doRTL' -->\n";

  $i = strpos($html,"\r\n\r\n");
  $headers = substr($html,0,$i-1);
  $content = substr($html,$i+4);
  if(preg_match('|Transfer-Encoding: chunke|Ui',$headers)) {
	  $Status .= "<!-- unchunking response -->\n"; 
	  $Status .= "<!-- in=".strlen($html);
      $html = preg_replace("|\r\n[0-9a-fA-F]+\r\n|is",'',$html); // kludge, but should get them all :)
	  $Status .= " out=".strlen($html). " bytes -->\n";
	}


 //  process the file .. select out the 7-day forecast part of the page
  $UnSupported = false;

// --------------------------------------------------------------------------------------------------
  
 $Status .= "<!-- processing JSON entries for forecast -->\n";
  $i = strpos($html,"\r\n\r\n");
  $headers = substr($html,0,$i-1);
  $content = substr($html,$i+4);
 

  $rawJSON = $content;
  $Status .= "<!-- rawJSON size is ".strlen($rawJSON). " bytes -->\n";

  $rawJSON = WC_prepareJSON($rawJSON);
  $JSON = json_decode($rawJSON,true); // get as associative array
  $Status .= WC_decode_JSON_error();
  if($doDebug) {$Status .= "<!-- JSON\n".print_r($JSON,true)." -->\n"; }

/* //Ken's dump debugging code
$Status = htmlentities($Status);
$Status = preg_replace("|\n|is","<br/>\n",$Status);
print $Status;
return;
//Ken's dump debugging code
*/ 


if(json_last_error() === JSON_ERROR_NONE) { // got good JSON .. process it
   $UnSupported = false;

   $WCforecastcity = $FCSTlocation;
   if($doIconv) {$WCforecastcity = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastcity);}
   if($doDebug) {
     $Status .= "<!-- WCforecastcity='$WCforecastcity' -->\n";
   }
   $WCtitle = "5-day Forecast";

	if($doDebug) {
		$Status .= "<!-- JSON daypart:0:daypartName count=" .
		  count( $JSON['daypart'][0]['daypartName']) . "-->\n";
	}
  $n = 0;
	$FCpart = $JSON['daypart'][0];
	$showTempsAs = ($WCunits == 'e')?'F':'C';

// process the forecast periods
$n = 0;
$firstIconPeriod = '';
  foreach ($FCpart['temperature'] as $i => $t) {
		if(empty($FCpart['daypartName'][$i])) {
			continue; // skip the empty ones if any
		}
    if ( $doDebug) {
				$Status .= "<!-- processing forecastday[$n]='" . $FCpart['daypartName'][$i] . "' -->\n";
		}
    $dayOrNight = $FCpart['dayOrNight'][$i];
		if($firstIconPeriod == '') { $firstIconPeriod = $dayOrNight; }
		if($dayOrNight == 'D') {
	    $WCforecasttemp[$n] = "<span style=\"color: #ff0000;\">".$t."&deg;$showTempsAs</span>";
		} else {
	    $WCforecasttemp[$n] = "<span style=\"color: #0000ff;\">".$t."&deg;$showTempsAs</span>";
		}


		$WCforecastday[$n] = trim($FCpart['daypartName'][$i]);
		if($doIconv) {$WCforecastday[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastday[$n]);}
		$WCforecasttitles[$n] = $WCforecastday[$n];
		if ($doDebug) {
				$Status .= "<!-- WCforecastday[$n]='" . $WCforecastday[$n] . "' -->\n";
		}	

		$WCforecasttext[$n] = trim($FCpart['narrative'][$i]);
		$WCforecasttext[$n] = str_replace("\xc2\xba","\xc2\xb0",$WCforecasttext[$n]); // fix wrong degree symbol
		if($doIconv) {$WCforecasttext[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecasttext[$n]);}
		if ($doDebug) {
				$Status .= "<!-- WCforecasttext[$n]='" . $WCforecasttext[$n] . "' -->\n";
		}
	
		$WCforecastpop[$n] = $FCpart['precipChance'][$i];
		if ($doDebug) {
				$Status .= "<!-- WCforecastpop[$n]='" . $WCforecastpop[$n] . "' -->\n";
		}

	  $WCforecastcond[$n] = $FCpart['wxPhraseLong'][$i]; 
	  $WCforecastcond[$n] = str_replace('/',', ',$WCforecastcond[$n]); 
		if($doIconv) {$WCforecastcond[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastcond[$n]);}
	  if ($doDebug) {
      $Status .= "<!-- forecastcond[$n]='" . $WCforecastcond[$n] . "' -->\n";
	  }

	$WCforecasticon[$n] = WC_img_replace($FCpart['iconCode'][$i],$dayOrNight,$WCforecastcond[$n],$WCforecastpop[$n]);
	
	if ($doDebug) {
      $Status .= "<!-- WCforecasticon[$n]='" . $WCforecasticon[$n] . "' -->\n";
	}	

	$WCforecasticons[$n] = $WCforecastday[$n] . "<br/>" .
	     $WCforecasticon[$n] . "<br/>" .
		 $WCforecastcond[$n];

	if(!empty($FCpart['thunderIndex'][$i]) and !empty($FCpart['thunderCategory'][$i])) {
		$WCforecastthunder[$n] = '<span style="color: black; background-color: #FD9125;">' .$FCpart['thunderCategory'][$i] . '</span>';
		if($doIconv) {$WCforecastthunder[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastthunder[$n]);}
	} else {
		$WCforecastthunder[$n] = '';
	}
	if ($doDebug) {
		$Status .= "<!-- WCforecastthunder[$n]='" . $WCforecastthunder[$n] . "' -->\n";
	}

	if(!empty($FCpart['uvIndex'][$i]) and $FCpart['uvIndex'][$i] > 0) {
		$WCforecastuv[$n] = 'UV: '. $FCpart['uvIndex'][$i] . ' <br/>'. WC_get_uvrange($FCpart['uvIndex'][$i],$FCpart['uvDescription'][$i]);
		if($doIconv) {$WCforecastuv[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastuv[$n]);}
	} else {
		$WCforecastuv[$n] = '';
	}
	if ($doDebug) {
		$Status .= "<!-- WCforecastuv[$n]='" . $WCforecastuv[$n] . "' -->\n";
	}

	if(!empty($FCpart['snowRange'][$i])) {
		$WCforecastsnow[$n] = $FCpart['snowRange'][$i]; 
		$WCforecastsnow[$n] .= ($WCunits == 'e')?' in':' cm';
		if($doIconv) {$WCforecastsnow[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastsnow[$n]);}
	} else {
		$WCforecastsnow[$n] = '';
	}
	if ($doDebug) {
		$Status .= "<!-- WCforecastsnow[$n]='" . $WCforecastsnow[$n] . "' -->\n";
	}

	if(!empty($FCpart['qpf'][$i]) and empty($FCpart['snowRange'][$i])) {
		$WCforecastrain[$n] = $FCpart['qpf'][$i];
		if($WCunits !== 'e') {$WCforecastrain[$n] = round($WCforecastrain[$n],1); }
		if($WCforecastrain[$n] == 0) {$WCforecastrain[$n] = '<1'; }  
		if($commaDecimal) {$WCforecastrain[$n] = str_replace('.',',',$WCforecastrain[$n]); }
		$WCforecastrain[$n] .= ($WCunits == 'e')?' in':' mm';
		if($doIconv) {$WCforecastrain[$n] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$WCforecastrain[$n]);}
	} else {
		$WCforecastrain[$n] = '';
	}
	if ($doDebug) {
		$Status .= "<!-- WCforecastrain[$n]='" . $WCforecastrain[$n] . "' -->\n";
	}
		 
	$n++;
  } // end of process json forecasts

} // end got good JSON decode/process
  
// end process JSON style --------------------------------------------------------------------

// All finished with parsing, now prepare to print
if(count($WCforecasticons) < 1) {
	print "Error: no forecast data was found in JSON from api.weather.gov.\n";
	return;
}
$wdth = intval(100/count($WCforecasticons));
$ndays = intval(count($WCforecasticon)/2);

$WCtitle = preg_replace('|5|i',$ndays,$WCtitle,1);

$doNumIcons = count($WCforecasticons); 

// establish what we're going to print based on the options set
$IncludeMode = false;
$PrintMode = true;

if (isset($doPrintWC) && ! $doPrintWC ) {
		print $Status;
		return;
}
if (isset($_REQUEST['inc']) && 
		strtolower($_REQUEST['inc']) == 'noprint' ) {
		print $Status;
	return;
}

if (isset($_REQUEST['inc']) && strtolower($_REQUEST['inc']) == 'y') {
  $IncludeMode = true;
}
if (isset($doIncludeWC)) {
  $IncludeMode = $doIncludeWC;
}

$printHeading = true;
$printIcons = true;
$printText = true;

if (isset($doPrintHeadingWC)) {
  $printHeading = $doPrintHeadingWC;
}
if (isset($_REQUEST['heading']) ) {
  $printHeading = substr(strtolower($_REQUEST['heading']),0,1) == 'y';
}

if (isset($doPrintIconsWC)) {
  $printIcons = $doPrintIconsWC;
}
if (isset($_REQUEST['icons']) ) {
  $printIcons = substr(strtolower($_REQUEST['icons']),0,1) == 'y';
}
if (isset($doPrintTextWC)) {
  $printText = $doPrintTextWC;
}
if (isset($_REQUEST['text']) ) {
  $printText = substr(strtolower($_REQUEST['text']),0,1) == 'y';
}

// ------- now do the actual HTML generation for the page

if (! $IncludeMode and $PrintMode) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WeatherUnderground <?php echo $WCtitle . ' - ' . $WCforecastcity; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charsetOutput; ?>" />
</head>
<body style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12px; background-color:#FFFFFF">

<?php
} // end printmode and not includemode
print $Status;
// if the forecast text is blank, prompt the visitor to force an update

if($UnSupported) {

  print <<< EONAG
<h1>Sorry.. this forecast can not be processed at this time.</h1>


EONAG
;
}

if (strlen($WCforecasttext[0])<2 and $PrintMode and ! $UnSupported ) {

  echo '<br/><br/>Forecast blank? <a href="' . $_SERVER['PHP_SELF'] . '?force=1">Force Update</a><br/><br/>';

} 
if ($PrintMode and ($printHeading or $printIcons)) {  ?>
  <table width="<?php print $maxWidth; ?>" style="border: none;" class="WCforecast">
  <?php echo $ddMenu ?>
    <?php if($printHeading) { ?>
    <tr align="center" style="background-color: #FFFFFF;">
      <td><b>WeatherUnderground <?php echo $WCtitle; ?>: </b><span style="color: green;">
	   <?php echo $WCforecastcity; ?></span>
      </td>
    </tr>
	<?php } // end print heading
	
if ($printIcons) {

// ------------ Icon printing new method -----------------

	$iconOrder = array( // defines which icons go where based on presentation selection
		'S' => array( array(0,1,2,3,4,5,6,7,8) ),  // One row (Straight Line)
		'TR' => array( array(0,1,2,3,4), array(5,6,7,8,9) ), // Two Rows
		'NO' => array( array(-1,1,3,5,7,9), array(0,2,4,6,8,10) ), // Night first icon, Day over Night
		'DO' => array( array(0,2,4,6,8,10),  array(1,3,5,7,9,11) )  // Day First icon, Day over Night
	);
	// if RTL, reverse 'em all
	
	if(isset($SITE['CSSscreen']) and $doRTL) {
		$doRTL = false;
		$Status .= "<!-- RTL language selected in Saratoga template.  \$doRTL = false; -->\n";
	}
	if($doRTL) {
		foreach ($iconOrder as $type => $MO) {
			foreach ($MO as $row => $cols) {
				$iconOrder[$type][$row] = array_reverse($iconOrder[$type][$row]);
			}
		}
	}
	
	/* just a bit of test code to show how it works
	
	print "<pre>\n";
	
	foreach ($iconOrder as $type => $MO) {
		print "--- $type ---\n";
		foreach ($MO as $row => $cols) {
			print "Row: $row ";
			foreach ($cols as $k => $idx) {
				print "($idx) ";
			}
			print " \n";
		}
		print "------------------\n";
	}
	
	print "</pre>\n";
	# end of test code
	//*/
	// see if we need to fold the icon rows due to long text length
	$doFoldRow = false; // don't assume we have to fold the row..
	if($foldIconRow) {
		$iTitleLen =0;
		$iTempLen = 0;
		$iCondLen = 0;
		for($i=0;$i<$doNumIcons;$i++) {
			$iTitleLen += strlen(strip_tags($WCforecasttitles[$i]));
			$iCondLen += strlen(strip_tags($WCforecastcond[$i]));
			$iTempLen += strlen(strip_tags($WCforecasttemp[$i]));  
		}
		print "<!-- lengths title=$iTitleLen cond=$iCondLen temps=$iTempLen -->\n";
		$maxChars = 135;
		if($iTitleLen >= $maxChars or 
			 $iCondLen >= $maxChars or
		   $iTempLen >= $maxChars ) {
			   print "<!-- folding icon row -->\n";
			   $doFoldRow = true;
		 } 
		 
	}
	
	if(isset($_REQUEST['foldrow']) and $_REQUEST['foldrow'] == 'y') {$doFoldRow = true;}

// set the icon display method based on settings
	
	$iList = $iconOrder['S'];  // assume single row
	if($doFoldRow) {
		$iList = $iconOrder['TR']; // folded row
	}
	
	if($iconRowDayNight) {      // day-over-night selected
		$iList = $iconOrder['DO'];   // use day-first order;
		if($firstIconPeriod == 'N') {
			$iList = $iconOrder['NO']; // use night-first order 
		}
	}
	if($doDebug) {print "<!-- order of icons selected is ".print_r($iList,true)." -->\n";}
	
	$wdth = round(100 / count($iList[0]));  // set percentage width for <td> elements based on icon display method
	$blankRow = array_fill(0,10,'&nbsp;<br/>&nbsp;');

	print "<tr>\n";
	print "  <td align=\"center\">\n";
	print "  <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n"; 

//  print the icons in the selected order	
  
	foreach ($iList as $k => $tRow) {
		WC_printRow($WCforecasttitles,$tRow,'font-size: 8pt;'); 
		WC_printRow($WCforecasticon,$tRow,''); 
		WC_printRow($WCforecastcond,$tRow,''); 
		WC_printRow($WCforecastrain,$tRow,'color: green;'); 
		WC_printRow($WCforecastsnow,$tRow,'color: darkblue;'); 
		WC_printRow($WCforecastthunder,$tRow,''); 
		WC_printRow($WCforecasttemp,$tRow,''); 
		WC_printRow($WCforecastuv,$tRow,''); 
		if(count($iList)>1) { # insert a blank row betweeen icon rows if needed
			WC_printRow($blankRow,$tRow,'');
		}

	} // end of print icons loop ?>
  </table><!-- end icon table -->
  </td>
  </tr>
<?php } // end print icons ?>    
</table><!-- end header+icons table -->
<p>&nbsp;</p>
<?php } // end print header or icons

if ($PrintMode and $printText) { 
// print the detail text forecasts ?>

<table style="border: 0" width="<?php print $maxWidth; ?>" class="WCforecast">
<?php
	for ($i=0;$i<count($WCforecasttitles);$i++) {
			print "  <tr valign =\"top\" align=\"left\">\n";
	if(!$doRTL) { // normal Left-to-right
			print "    <td style=\"width: 20%;\"><b>$WCforecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
			print "    <td style=\"width: 80%;\">$WCforecasttext[$i]</td>\n";
	} else { // print RTL format
			print "    <td style=\"width: 80%; text-align: right;\">$WCforecasttext[$i]</td>\n";
			print "    <td style=\"width: 20%; text-align: right;\"><b>$WCforecasttitles[$i]</b><br />&nbsp;<br /></td>\n";
	}
	print "  </tr>\n";
	}
?>
</table>
<?php } // end print text ?>
<?php if ($PrintMode) { 
// print the required footer information ?>
<p>&nbsp;</p>
<?php
 print '<p class="WCforecast">';
 print langtransstr('WeatherUnderground forecast for'); ?> 
 <a href="https://www.wunderground.com/cgi-bin/findweather/getForecast?query=<?php echo $fileName; ?>"> 
 <?php echo $WCforecastcity; ?></a>.
<?php if($iconType <> '.jpg') {
	// print credit for .gif icons if used
	print "<br/>";
	print langtransstr('Animated forecast icons courtesy of');
	print " <a href=\"https://www.meteotreviglio.com/\">www.meteotreviglio.com</a>.";
} 
?>
</p>
<?php
} // end printmode

 if (! $IncludeMode and $PrintMode ) { ?>
</body>
</html>
<?php 
}  
// END of main script -----------------------------------------------------------------------
 
// Functions --------------------------------------------------------------------------------

// get contents from one URL and return as string 
function WC_fetchUrlWithoutHanging($url,$useFopen) {
  global $Status, $needCookie;
  
  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=4;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Status .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (WC-forecast.php - saratoga-weather.org)');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Status .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Status .= "<!-- Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Status .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'] .
    " dest=".$cinfo['primary_ip'] ;
	if(isset($cinfo['primary_port'])) { 
	  $Status .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Status .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Status .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Status .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Status .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Status .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Status .= "<!-- raw data\n".$data."\n -->\n"; 
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
  if($cinfo['http_code'] <> 200) {
    $Status .= "<!-- headers:\n".$headers."\n -->\n"; 
  }
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (WC-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (WC-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = WC_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = WC_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Status .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Status .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Status .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n"; 
   return($xml);
 }

}    // end WC_fetch_URL

// ------------------------------------------------------------------

function WC_fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


// ------------------------------------------------------------------
   
 function WC_img_replace ( $WCimage, $dayOrNight, $WCcondtext,$WCpop) {
//
// optionally replace the WeatherUnderground icon with an NWS icon instead.
// 
 global $NWSiconlist,$iconDir,$iconType,$Status;
 $dn = ($dayOrNight == 'D')?0:1;
 $curicon = isset($NWSiconlist[$WCimage][$dn])?$NWSiconlist[$WCimage][$dn]:''; // translated icon (if any)

 if (!$curicon) { // no change.. use WC icon
   $timg = sprintf('%.02d',$WCimage).'.png';
   return("<img src=\"$iconDir$timg\" width=\"55\" height=\"55\" 
  alt=\"$WCcondtext\" title=\"$WCcondtext\"/>"); 
 }
  if($iconType <> '.jpg') {
	  $curicon = preg_replace('|\.jpg|',$iconType,$curicon);
  }
  $Status .= "<!-- replace icon '$WCimage' with ";
  if ($WCpop > 0) {
	$testicon = preg_replace('|'.$iconType.'|',$WCpop.$iconType,$curicon);
	if (file_exists("$iconDir$testicon")) {
	  $newicon = $testicon;
	} else {
	  $newicon = $curicon;
	}
  } else {
	$newicon = $curicon;
  }
  $Status .= "'$newicon' pop=$WCpop -->\n";

  return("<img src=\"$iconDir$newicon\" width=\"55\" height=\"58\" 
  alt=\"$WCcondtext\" title=\"$WCcondtext\"/>"); 
 
 
 }

// ------------------------------------------------------------------
 
function WC_prepareJSON($input) {
	global $Status;
   
   //This will convert ASCII/ISO-8859-1 to UTF-8.
   //Be careful with the third parameter (encoding detect list), because
   //if set wrong, some input encodings will get garbled (including UTF-8!)

   list($isUTF8,$offset,$msg) = WC_check_utf8($input);
   
   if(!$isUTF8) {
	   $Status .= "<!-- WC_prepareJSON: Oops, non UTF-8 char detected at $offset. $msg. Doing utf8_encode() -->\n";
	   $str = utf8_encode($input);
       list($isUTF8,$offset,$msg) = WC_check_utf8($str);
	   $Status .= "<!-- WC_prepareJSON: after utf8_encode, i=$offset. $msg. -->\n";   
   } else {
	   $Status .= "<!-- WC_prepareJSON: $msg. -->\n";
	   $str = $input;
   }
  
   //Remove UTF-8 BOM if present, json_decode() does not like it.
   if(substr($str, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) $str = substr($str, 3);
   
   return $str;
}

// ------------------------------------------------------------------

function WC_check_utf8($str) {
// check all the characters for UTF-8 compliance so json_decode() won't choke
// Sometimes, an ISO international character slips in the WC text string.	  
     $len = strlen($str); 
     for($i = 0; $i < $len; $i++){ 
         $c = ord($str[$i]); 
         if ($c > 128) { 
             if (($c > 247)) return array(false,$i,"c>247 c='$c'"); 
             elseif ($c > 239) $bytes = 4; 
             elseif ($c > 223) $bytes = 3; 
             elseif ($c > 191) $bytes = 2; 
             else return false; 
             if (($i + $bytes) > $len) return array(false,$i,"i+bytes>len bytes=$bytes,len=$len"); 
             while ($bytes > 1) { 
                 $i++; 
                 $b = ord($str[$i]); 
                 if ($b < 128 || $b > 191) return array(false,$i,"128<b or b>191 b=$b"); 
                 $bytes--; 
             } 
         } 
     } 
     return array(true,$i,"Success. Valid UTF-8"); 
 } // end of check_utf8

// ------------------------------------------------------------------
 
function WC_decode_JSON_error() {
	
  $Status = '';
  $Status .= "<!-- json_decode returns ";
  switch (json_last_error()) {
	case JSON_ERROR_NONE:
		$Status .= ' - No errors';
	break;
	case JSON_ERROR_DEPTH:
		$Status .= ' - Maximum stack depth exceeded';
	break;
	case JSON_ERROR_STATE_MISMATCH:
		$Status .= ' - Underflow or the modes mismatch';
	break;
	case JSON_ERROR_CTRL_CHAR:
		$Status .= ' - Unexpected control character found';
	break;
	case JSON_ERROR_SYNTAX:
		$Status .= ' - Syntax error, malformed JSON';
	break;
	case JSON_ERROR_UTF8:
		$Status .= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
	break;
	default:
		$Status .= ' - Unknown error, json_last_error() returns \''.json_last_error(). "'";
	break;
   } 
   $Status .= " -->\n";
   return($Status);
}

// ------------------------------------------------------------------

function WC_get_APIURL ($rawLatLong) {
	global $WCAPIkey,$WCLANG,$WCunits,$Status,$doDebug;

// try to generate an API request URL from a WC lat/long	

   $newURL = 'https://api.weather.com/v3/wx/forecast/daily/5day?format=json&units=%s&language=%s&apiKey=%s&geocode=%s';

	 $newURL = sprintf($newURL,$WCunits,$WCLANG,$WCAPIkey,$rawLatLong); // add the boilerplate stuff

   $Status .= "<!-- WC_API Raw LatLong='$rawLatLong' -->\n";
   $Status .= "<!-- WC API New URL='$newURL' -->\n";
	 return($newURL);
	
}

// ------------------------------------------------------------------

function WC_loadLangDefaults () {
	global $WClanguages, $WClangCharsets;
/*
Languages from https://docs.google.com/document/d/13HTLgJDpsb39deFzk_YCQ5GoGoZCO_cRYzIxbwvgJLI/edit#

ar-AE	Arabic - (United Arab Emirates)
az-AZ	Azerbaijani - (Azerbaijan)
bg-BG	Bulgarian - (Bulgaria)
bn-BD	Bengali, Bangla - (Bangladesh)
bn-IN	Bengali, Bangla - (India)
bs-BA	Bosnian - (Bosnia and Herzegovina)
ca-ES	Catalan - (Spain)
cs-CZ	Czech - (Czechia)
da-DK	Danish - (Denmark)
de-DE	German - (Germany)
el-GR	Greek (modern) - (Greece)
en-GB	English (Great Britain)
en-IN	English - (India)
en-US	English - (United States of America)
es-AR	Spanish - (Argentina)
es-ES	Spanish - (Spain)
es-LA	Spanish - (Latin America)
es-MX	Spanish - (Mexico)
es-UN	Spanish - (International)
es-US	Spanish - (United States of America)
et-EE	Estonian - (Estonia)
fa-IR	Persian (Farsi) - (Iran)
fi-FI	Finnish - (Finland)
fr-CA	French - (Canada)
fr-FR	French - (France)
gu-IN	Gujarati - (India)
he-IL	Hebrew (modern) - (Israel)
hi-IN	Hindi - (India)
hr-HR	Croatian - (Croatia)
hu-HU	Hungarian - (Hungary)
in-ID	Indonesian - (Indonesia)
is-IS	Icelandic - (Iceland)
it-IT	Italian - (Italy)
iw-IL	Hebrew - (Israel)
ja-JP	Japanese - (Japan)
jv-ID	Javanese - (Indonesia)
ka-GE	Georgian - (Georgia)
kk-KZ	Kazakh - (Kazakhstan)
kn-IN	Kannada - (India)
ko-KR	Korean - (South Korea)
lt-LT	Lithuanian - (Lithuania)
lv-LV	Latvian - (Latvia)
mk-MK	Macedonian - (Macedonia)
mn-MN	Mongolian - (Mongolia)
ms-MY	Malay - (Malaysia)
nl-NL	Dutch - (Netherlands)
no-NO	Norwegian - (Norway)
pl-PL	Polish - (Poland)
pt-BR	Portuguese - (Brazil)
pt-PT	Portuguese - (Portugal)
ro-RO	Romanian - (Romania)
ru-RU	Russian - (Russia)
si-LK	Sinhalese, Sinhala - (Sri Lanka)
sk-SK	Slovak - (Slovakia)
sl-SI	Slovenian - (Slovenia)
sq-AL	Albanian - (Albania)
sr-BA	Serbian - (Bosnia and Herzegovina)
sr-ME	Serbian - (Montenegro)
sr-RS	Serbian - (Serbia)
sv-SE	Swedish - (Sweden)
sw-KE	Swahili - (Kenya)
ta-IN	Tamil - (India)
ta-LK	Tamil - (Sri Lanka)
te-IN	Telugu - (India)
tg-TJ	Tajik - (Tajikistan)
th-TH	Thai - (Thailand)
tk-TM	Turkmen - (Turkmenistan)
tl-PH	Tagalog - (Philippines)
tr-TR	Turkish - (Turkey)
uk-UA	Ukrainian - (Ukraine)
ur-PK	Urdu - (Pakistan)
uz-UZ	Uzbek - (Uzbekistan)
vi-VN	Vietnamese - (Viet Nam)
zh-CN	Chinese - (China)
zh-HK	Chinese - (Hong Kong)
zh-TW	Chinese - (Taiwan)

*/
 
 $WClanguages = array(  // our template language codes v.s. lang:LL codes for JSON
//	'af' => 'AF',
	'bg' => 'bg-BG',
	'cs' => 'cs-CZ',
	'ct' => 'ca-ES',
	'dk' => 'da-DK',
	'nl' => 'nl-NL',
	'en' => 'en-US',
	'fi' => 'fi-FI',
	'fr' => 'fr-FR',
	'de' => 'de-DE',
	'el' => 'el-GR',
//	'ga' => 'IR',
	'it' => 'it-IT',
	'he' => 'he-IL',
	'hu' => 'hu-HU',
	'no' => 'no-NO',
	'pl' => 'pl-PL',
	'pt' => 'pt-PT',
	'ro' => 'ro-RO',
	'es' => 'es-ES',
	'se' => 'sv-SE',
	'si' => 'sl-SI',
	'sk' => 'sk-SK',
	'sr' => 'sr-RS',
  );

  $WClangCharsets = array(
	'bg' => 'ISO-8859-5',
	'el' => 'ISO-8859-7',
	'he' => 'UTF-8', 
	'hu' => 'ISO-8859-2',
	'ro' => 'ISO-8859-2',
	'pl' => 'ISO-8859-2',
	'si' => 'ISO-8859-2',
	'ru' => 'ISO-8859-5'
  );

} // end loadLangDefaults

// ------------------------------------------------------------------
//  decode UV to word+color for display

function WC_get_UVrange ( $inUV, $inUVwords ) {
// figure out a text value and color for UV exposure text
//  0 to 2  Low
//  3 to 5 	Moderate
//  6 to 7 	High
//  8 to 10 Very High
//  11+ 	Extreme
   if(strpos($inUV,',') !== false ) {
	   $uv = preg_replace('|,|','.',$inUV);
   } else {
	   $uv = $inUV;
   }
   switch (TRUE) {
     case ($uv == 0):
       $uv = '';
     break;
     case (($uv > 0) and ($uv < 3)):
       $uv = '<span style="border: solid 1px; color: black; background-color: #A4CE6a;">&nbsp;' . $inUVwords . '&nbsp;</span>';
     break;
     case (($uv >= 3) and ($uv < 6)):
       $uv = '<span style="border: solid 1px; color: black; background-color: #FBEE09;">&nbsp;' . $inUVwords . '&nbsp;</span>';
     break;
     case (($uv >=6 ) and ($uv < 8)):
       $uv = '<span style="border: solid 1px; color: black; background-color: #FD9125;">&nbsp;' . $inUVwords . '&nbsp;</span>';
     break;
     case (($uv >=8 ) and ($uv < 11)):
       $uv = '<span style="border: solid 1px; color: #FFFFFF; background-color: #F63F37;">&nbsp;' . $inUVwords . '&nbsp;</span>';
     break;
     case (($uv >= 11) ):
       $uv = '<span style="border: solid 1px; color: #FFFF00; background-color: #807780;">&nbsp;' . $inUVwords . '&nbsp;</span>';
     break;
   } // end switch
   return $uv;
} // end WC_get_UVrange

// ------------------------------------------------------------------
// print one row of WC data in table (V1.03+)

function WC_printRow($WCvar,$indexList,$style="") {
	global $wdth,$doRTL,$doDebug;
	
	if(!$doRTL) {
			print "  <tr valign=\"top\" align=\"center\">\n";
	} else {
			print "  <tr valign=\"top\" align=\"center\" style=\"direction: rtl\">\n";
	}
 
 foreach ($indexList as $k => $idx) {
	 $cm = $doDebug?"<!-- $idx -->":'';
	 if(isset($WCvar[$idx])) {
  	 print "    <td style=\"width: $wdth%; text-align: center;$style\">$WCvar[$idx]</td>$cm\n";
	 } else {
  	 print "    <td style=\"width: $wdth%; text-align: center;\"> </td>$cm\n";
	 }
 }
   print "  </tr>\n";	
	
} // end WC_printRow


// End of functions --------------------------------------------------------------------------

?>