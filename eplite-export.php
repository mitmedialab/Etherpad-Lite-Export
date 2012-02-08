<?php
include('lib/spyc.php');

/**
 * This is a super-quick script that exports the contents of your Etherpad Lite MySql store to
 * static HTML.  I created this because it was becoming a critical part of our infrastructure and
 * thus neeeded some kind of readable backup system.
 */

# load settings
define('VERSION', '0.1');
define('CONFIG_FILE', "settings.yml");
if(!file_exists(CONFIG_FILE)) {
  print("ERROR: you need to create a settings.yml, based on settings.yml.template\n");
  exit(1);
}
$config = Spyc::YAMLLoad(CONFIG_FILE);

# connect to db
$db = mysql_connect($config['db']['host'],$config['db']['username'],$config['db']['password']);
$result = mysql_select_db($config['db']['database']);

# print summary info
print("Starting eplite export (v".VERSION.")\n");
$results = mysql_query("SELECT count(*) as total FROM store");
$row = mysql_fetch_assoc($results);
$total = $row['total'];
print("  Found $total rows in the store\n");
$results = mysql_query("SELECT count(*) as total FROM  `store` ".
    "WHERE  `key` NOT LIKE  '%:revs:%' AND  `key` LIKE  'pad:%' AND `key` NOT LIKE  '%:chat:%'");
$row = mysql_fetch_assoc($results);
$total = $row['total'];
print("  Found $total unique pads in the store\n");

# helper function - start an html file
function start_html_file($file, $title) {
  fwrite($file,"<html>\n");
  fwrite($file,"<head>\n");
  fwrite($file,'<meta http-equiv="content-type" content="text/html;charset=utf-8"/>'."\n");
  fwrite($file,"<title>$title : Etherpad-Lite Export</title>");
  fwrite($file,"</head>\n");
  fwrite($file,"<body>\n");
}

# helper function - end an html file
function end_html_file($file){
  fwrite($file,"</html>\n");
  fwrite($file,"</body>\n");
}

# setup export dirs
$now = time();
$export_dirname = "eplite-backup";
if($config['timestamp']) {
    $export_dirname = $export_dirname . date("Ymd-His",$now);
}
$export_path = $config['backup_dir']."/".$export_dirname;
if(!file_exists($export_path)){
    mkdir($export_path);
}
$pad_export_path = $export_path."/pads";
if(!file_exists($pad_export_path)){
    mkdir($pad_export_path);
}
print ("  Exporting to $export_path\n");

# start the toc
$index = fopen($export_path."/index.html",'w');
start_html_file($index, "Table Of Contents");
fwrite($index,"<h1>Table of Contents</h1>");
fwrite($index,"<ul>\n");
$server_toc = fopen($export_path."/server-toc.html",'w');
start_html_file($server_toc, "Table Of Contents");
fwrite($server_toc,"<h1>Table of Contents</h1>");
fwrite($server_toc,"<ul>\n");

# go through all the pad master entries, saving the content of each
$results = mysql_query("SELECT * FROM  `store` WHERE  `key` NOT LIKE  '%:revs:%' AND  `key` LIKE  'pad:%' AND `key` NOT LIKE  '%:chat:%' ORDER BY `key`");
while ($row = mysql_fetch_assoc($results)) {
  $title = str_replace("pad:","",$row['key']);
  $pad_value = json_decode($row['value']);
  $contents = $pad_value->atext->text;
  # http://www.stemkoski.com/php-remove-non-ascii-characters-from-a-string/
  $filename = preg_replace('/[^(\x20-\x7F)]*/','', $title).".html";
  # add an item to the table of contents
  fwrite($index,"  <li><a href=\"pads/$filename\">$title</a></li>\n");
  fwrite($server_toc,"  <li><a href=\"".$config['base_url']."p/$title\">$title</a></li>\n");
  # export the contents too
  $pad_file = fopen($pad_export_path."/".$filename,'w');
  start_html_file($pad_file, $title);
  fwrite($pad_file,"<pre>\n");
  fwrite($pad_file,"$contents\n");
  fwrite($pad_file,"</pre>\n");
  end_html_file($pad_file);
  fclose($pad_file);
}

fwrite($index,"</ul>\n");
fwrite($server_toc,"</ul>\n");

# cleanup
end_html_file($index);
end_html_file($server_toc);
fclose($index);
fclose($server_toc);
mysql_close($db);

print("Done");
