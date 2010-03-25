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

            $matches = array();
            //find class information
            $pattern = "/\>\w{4}\s*(\d{4})(\s*-\s*\d{4})?\<.*?href=\S+?course=(\d+)[^\>]*?\>([^\<]*?)\<";
            //try for prereq/coreq information. We'll have to split this out in another pass...
            $pattern .= "((?:[^:]*?(?:Prerequisite|Corequisite).?(?:[^:]*?Corequisite)?\s*:(?:[^:]*?\w{4}\s*\d{4})+)*";
            //grap semester/year offering info if it exists
            $pattern .= "(?:[^\)]*?\((\s*(spring|fall)[^\)]*?)?(even|odd.*?)?\))?";
            //make sure we didn't accidentally gobble into the next class
            $pattern .= "(?!=\<\div\>))?";
            $pattern .= "/is";
            preg_match_all($pattern, $data, $matches, PREG_SET_ORDER);
//            dump("matches", $matches); continue;
            foreach($matches as $match) {
                $fields = array();
                $fields["departmentID"] = $deptID;
                $fields["number"] = $match[1];
                $fields["title"] = $match[4];
                $fields["linkid"] = $match[3];
                $fields["hours"] = substr($fields["number"], -1);
                if(!empty($match[7])) {
                    $fields["years"] = (stristr($match[7], "even") == false)?1:2;
                }
                if(!empty($match[6])) {
                    $fields["offered"] = (stristr($match[6], "fall") == false)?1:2;
                }
                $db->insert("classes", $fields);
                $classID = $db->getLastInsertID();

                if(!empty($match[5])) {
                    $matches2 = explode(". ", $match[5]);
                    foreach($matches2 as $match2) {
                        if(empty($match2)) continue;
                        $matches3 = array();
                        $preco = 0;
                        if(stristr($match2, "prereq") !== false) {
                            if(stristr($match2, "coreq") !== false) {
                                $preco = 3;
                            } else {
                                $preco = 1;
                            }
                        } elseif(stristr($match2, "coreq") !== false) {
                            $preco = 2;
                        }
                        preg_match_all("/(\w{4})\s*(\d{4})/is", $match2, $matches3, PREG_SET_ORDER);
                        $fields = array();
                        $fields["classID"] = $classID;
                        $fields["type"] = $preco;
                        foreach($matches3 as $match3) {
                            $fields["department"] = $match3[1];
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
    while($row = $result->fetchArray()) {
    }
    /*
    $fields[] = new DBField("classID", DBField::NUM, -1, "classes");
    $fields[] = new DBField("requiresClassID", DBField::NUM, null, "classes");
    $fields[] = new DBField("type", DBField::NUM, 0); //none, prereq, coreq, either
    //we might not have added this class yet, so these will have to be evaluated to a requiresClassID in a future pass
    $fields[] = new DBField("department", DBField::STRING);
    $fields[] = new DBField("courseNumber", DBField::NUM);
    $db->createTable("classDependencyMap", $fields);
    */
?>