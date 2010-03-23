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
            $matches = array();
            preg_match("/.majorTitle.+?>(.+?)\s*\((\w+)\)\s*\</is", $data, $matches);
            die($data);
            $fields = array();
            $fields["department"] = $matches[2];
            $fields["title"] = $matches[1];
            $fields["id"] = $match[1];
            $db->insert("departments", $fields);
            $deptID = $db->getLastInsertID();

            $matches = array();
            preg_match_all("//is", $data, $matches, PREG_SET_ORDER);
            foreach($matches as $match) {
                $fields = array();
            }
            /*
            $fields[] = new DBField("departmentID", DBField::NUM, -1, "departments", "ROWID");
            $fields[] = new DBField("number", DBField::NUM);
            $fields[] = new DBField("title", DBField::STRING);
            $fields[] = new DBField("id", DBField::STRING);
            $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
            $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
            $fields[] = new DBField("hours", DBField::NUM, 3);
            */

            /*
            $fields[] = new DBField("classID", DBField::NUM, -1, "classes", "ROWID");
            $fields[] = new DBField("requiresClassID", DBField::NUM, null, "classes", "ROWID");
            $fields[] = new DBField("type", DBField::NUM, 0); //none, prereq, coreq, either
            //we might not have added this class yet, so these will have to be evaluated to a requiresClassID in a future pass
            $fields[] = new DBField("departmentID", DBField::NUM);
            $fields[] = new DBField("courseNumber", DBField::NUM);
            */
        }
    }
?>