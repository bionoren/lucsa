<?php
    require_once("functions.php");
    require_once("SQLiteManager.php");

    $db = new SQLiteManager("lucsa.sqlite");
    $year = "2009";
    $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year);
    $matches = array();
    //find the departments in each school
    $pattern = "/(?:\<option[^\>]*?\>School.+?\<.+?\>\s*(?:\<option[^\>]+?value=.\d+[^\>]+?\>(?:&nbsp;)+.*?\<.*?\>\s*)+)+";
    //tack on the honors and LETU course sections
    $pattern .= "(?:\<option.+?value=.\d+.+?\>.+?\<.+?\>\s*){2}";
    $pattern .= "/is";
    preg_match($pattern, $data, $matches);
    $data = preg_replace("/\<[^\>]*?\>School.*?\<.*?\>\s*/is", "", $matches[0]);
    $matches = array();
    preg_match_all("/\<option.*?value=.(\d+)/is", $data, $matches, PREG_SET_ORDER);
    foreach($matches as $match) {
        $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]);
        $matches = array();
        preg_match_all("/\<a[^\>]+?(\d+)&cmd=courselist/is", $data, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]."&cmd=courselist");
            die($data);
        }
    }
?>