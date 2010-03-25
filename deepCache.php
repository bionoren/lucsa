<?php
    require_once("functions.php");
    require_once("SQLiteManager.php");
    require_once("dbinit.php");

    $db = new SQLiteManager("lucsa.sqlite");
    $year = "2009";
    $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year);
    $matches1 = array();
    preg_match_all("/\<option[^\>]*?value=.(\d+)[^\>]*?\>(?:School|honors|LeTourneau).*?\</is", $data, $matches1, PREG_SET_ORDER);
//    dump("matches1", $matches1);
    $departmentLookup = array();
    foreach($matches1 as $match) {
//        print "url = http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]."<br>";
        $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]);
        $matchesdepnum = array();
        preg_match_all("/\<a[^\>]+?school=(\d+)[^\>]*?cmd=courselist.*?\>\s*\d{1,3}\D/is", $data, $matchesdepnum, PREG_SET_ORDER);
//        dump("matches", $matchesdepnum); continue;
        foreach($matchesdepnum as $match) {
            $data = file_get_contents("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]."&cmd=courselist");
            $matches = array();
            preg_match("/.majorTitle.+?>(.+?)\s*\((\w+)\)\s*\</is", $data, $matches);
//            die($data);
            $fields = array();
//            print "adding department ".$matches[2]." - ".$match[1]."<br>"; continue;
            $fields["department"] = $matches[2];
            $fields["title"] = $matches[1];
            $fields["linkid"] = $match[1];
            $db->insert("departments", $fields);
            $deptID = $db->getLastInsertID();
            $departmentLookup[$fields["department"]] = $deptID;

            $matchGroups = preg_split("/\<\/div\>.*?\<div.*?\>/is", $data);
            $matches = array();
            foreach($matchGroups as $match) {
                $pattern = "/.*?\>\s*(\w{4})\s*(\d{4}).*?\<a[^\>]*course=(\d+)[^\>]*\>(.*?)\<";
                //try for prereq/coreq information. We'll have to split this out in another pass...
                $pattern .= ".*?((?:prereq|coreq).*)(\(.*?\))?";
                //grap semester/year offering info if it exists
                $pattern .= "/is";
                $tmp = array();
                if(preg_match($pattern, $match, $tmp) != 0) {
                    $matches[] = $tmp;
                }
            }

/*            $matches = array();
            //find class information
            $pattern = "/\<div([^\>]*\>(?!\s*\<\/div))*?(\w{4})\s*(\d{4})([^\>]*\>(?!\s*\<\/a))*";
            $pattern .= "[^\>]*course=(\d+)[^\>]*\>([^\<]*)";
            //try for prereq/coreq information. We'll have to split this out in another pass...
            $pattern .= "([^\>]*\>(?!\s*(prereq|coreq|\(|\<\/div)))*((prereq|coreq)([^\>]*\>(?!\s*(\(|\<\/div)))*";
            //grap semester/year offering info if it exists
            $pattern .= "(\([^\)]*\))?)";
            $pattern .= "/is";
            preg_match_all($pattern, $data, $matches, PREG_SET_ORDER);*/
//            dump("matches", $matches); continue;
            foreach($matches as $match) {
                $fields = array();
                $fields["departmentID"] = $deptID;
                $fields["number"] = $match[1];
                $fields["title"] = $match[4];
                $fields["linkid"] = $match[3];
                $fields["hours"] = substr($fields["number"], -1);
                if(!empty($match[6])) {
                    $search = stristr($match[6], "even");
                    if($search !== false) {
                        $fields["years"] = ($search == false)?1:2;
                    }
                    $search = stristr($match[6], "fall");
                    if($search !== false) {
                        $fields["offered"] = ($search == false)?1:2;
                    }
                }
                $db->insert("classes", $fields);
                $classID = $db->getLastInsertID();

                if(!empty($match[5])) {
                    //that whole regex is COMPLICATED, so we have to patch it up here.
//                    $match[5] = preg_replace("/.*?(?=(prereq|coreq))/is", "", $match[5]);
                    $matches2 = explode(". ", $match[5]);
                    $preco = 0;
                    foreach($matches2 as $match2) {
                        if(empty($match2)) continue;
                        $matches3 = array();
                        if(stristr($match2, "prereq") !== false) {
                            if(stristr($match2, "coreq") !== false) {
                                $preco = 3;
                            } else {
                                $preco = 1;
                            }
                        } elseif(stristr($match2, "coreq") !== false) {
                            $preco = 2;
                        }
                        preg_match_all("/\>\s*(\w{4})\s*(\d{4})\s*\</is", $match2, $matches3, PREG_SET_ORDER);
                        $fields = array();
                        $fields["classID"] = $classID;
                        $fields["type"] = $preco;
                        foreach($matches3 as $match3) {
                            $fields["department"] = strtoupper($match3[1]);
                            $fields["courseNumber"] = $match3[2];
                            $db->insert("classDependencyMap", $fields);
                        }
                    }
                }
            }
        }
    }
    //create proper class ids for class dependencies
    $result = $db->query("SELECT * FROM classDependencyMap");
    $fields = array();
    $whereFields = array();
//    dump("departments", $departmentLookup);
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
//        dump("row", $row);
        $result2 = $db->query("SELECT ID FROM classes WHERE departmentID=".$departmentLookup[$row["department"]]." AND number=".$row["courseNumber"]);
        $row2 = $result2->fetchArray(SQLITE3_ASSOC);
        $fields["requiresClassID"] = $row2["ID"];
        $whereFields["id"] = $row["id"];
        $db->update("classDependencyMap", $fields, $whereFields);
    }
?>