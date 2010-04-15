<?php
    /*
	 *	Copyright 2010 Bion Oren
	 *
	 *	Licensed under the Apache License, Version 2.0 (the "License");
	 *	you may not use this file except in compliance with the License.
	 *	You may obtain a copy of the License at
	 *		http://www.apache.org/licenses/LICENSE-2.0
	 *	Unless required by applicable law or agreed to in writing, software
	 *	distributed under the License is distributed on an "AS IS" BASIS,
	 *	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	 *	See the License for the specific language governing permissions and
	 *	limitations under the License.
	 */

    unset($_SERVER['PHP_AUTH_USER']);
    session_start();
    unset($_SESSION["userID"]);
    session_write_close();
    require_once("functions.php");
    require_once("SQLiteManager.php");
    require_once("dbinit.php");

    $findSchoolsPattern = "/\<option[^\>]*?value=.(\d+)[^\>]*?\>(?:School|honors|LeTourneau).*?\</is";

    $departmentPattern = "/.majorTitle.+?>(.+?)\s*\((\w+)\)\s*\</is";

    $fetchSchoolsPattern = "/\<a[^\>]+?school=(\d+)[^\>]*?cmd=courselist.*?\>\s*\d{1,3}\D/is";

    $classSplitPattern = "/\<\/div\>.*?\<div.*?\>/is";

    $classParsePattern = "/.*?\>\s*(?P<dept>\w{4})\s*(?P<num>\d{4}(-\d{4})?).*?\<a[^\>]*?course=(?P<linkid>\d+)[^\>]*\>\s*(?P<title>[^\<]*?)\s*\<";
    //try for prereq/coreq information. We'll have to split this out in another pass...
    $classParsePattern .= "(?:[^\>]+\>\s*(?!Prereq|Coreq))+(?P<extra>[^\(]*(?P<time>\(\s*[^\)]*\s*\))?)?";
    //grap semester/year offering info if it exists
    $classParsePattern .= "/isS";

    $classExtraPattern = "/\>\s*(\w{4})\s*(\d{4})\s*\</is";

    $db = new SQLiteManager("lucsa.sqlite");
    $yearresult = $db->query("SELECT * FROM years");// WHERE updated&1=0");
//    $yearArray = $yearresult->fetchArray(SQLITE3_ASSOC);
//    $year = $yearArray["year"];
//    $yearID = $yearArray["ID"];
    $departmentLookup = array();
while($row = $yearresult->fetchArray(SQLITE3_ASSOC)) {
    $year = $row["year"];
    $yearID = $row["ID"];

    $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year);
    $matches1 = array();
    preg_match_all($findSchoolsPattern, $data, $matches1, PREG_SET_ORDER);
//    dump("matches1", $matches1); die();
    foreach($matches1 as $match) {
//        print "url = http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]."<br>";
        $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]);
        $matchesdepnum = array();
        preg_match_all($fetchSchoolsPattern, $data, $matchesdepnum, PREG_SET_ORDER);
//        dump("matches", $matchesdepnum); continue;
        foreach($matchesdepnum as $match) {
            $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&school=".$match[1]."&cmd=courselist");
            $matches = array();
            preg_match($departmentPattern, $data, $matches);
//            die($data);
//            print "adding department ".$matches[2]." - ".$match[1]."<br>"; continue;
            if(!isset($departmentLookup[$matches[2]])) {
                $fields = array();
                $fields["department"] = $matches[2];
                $fields["title"] = $matches[1];
                $fields["linkid"] = $match[1];
                $db->insert("departments", $fields, true);
                $deptID = $db->getLastInsertID();
                $departmentLookup[$fields["department"]] = $deptID;
            } else {
                $deptID = $departmentLookup[$matches[2]];
            }

            $matchGroups = preg_split($classSplitPattern, $data);
            $matches = array();
            foreach($matchGroups as $match) {
                $tmp = array();
                if(preg_match($classParsePattern, $match, $tmp) != 0) {
//                    print $match."<br>\n";
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
                $fields["yearID"] = $yearID;
                $number = $match["num"];
                $number = explode("-", $number);
                $fields["number"] = $number[0];
                if(count($number) > 1) {
                    $fields["endNumber"] = $number[1];
                } else {
                    $fields["endNumber"] = $fields["number"];
                }
                $fields["title"] = $match["title"];
                $fields["linkid"] = $match["linkid"];
                $fields["hours"] = substr($fields["number"], -1);
                if(!empty($match["time"])) {
//                    print "time<br>";
                    $search = stristr($match["time"], "even");
                    if($search !== false) {
                        $fields["years"] = ($search == false)?1:2;
                    }
                    $search = stristr($match["time"], "fall");
                    if($search !== false) {
                        $fields["offered"] = ($search == false)?1:2;
                    }
                }
                $db->insert("classes", $fields);
                $classID = $db->getLastInsertID();

                if(!empty($match["extra"])) {
//                    print "extra<br>";
                    //that whole regex is COMPLICATED, so we have to patch it up here.
                    $matches2 = explode(". ", $match["extra"]);
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
                        preg_match_all($classExtraPattern, $match2, $matches3, PREG_SET_ORDER);
                        $fields = array();
                        $fields["classID"] = $classID;
                        $fields["type"] = $preco;
                        foreach($matches3 as $match3) {
                            $fields["department"] = strtoupper($match3[1]);
                            $fields["courseNumber"] = $match3[2];
//                            dump("fields", $fields);
                            $db->insert("classDependencyMap", $fields);
                        }
                    }
                }
            }
        }
    }

    //add transfer credit class
/*    $fields = array();
    $fields["department"] = "LETU";
    $fields["title"] = "LeTourneau University";
    $fields["linkid"] = "1543";
    $db->insert("departments", $fields, true);*/

    $sql = "SELECT ID FROM departments WHERE department='LETU'";
    $result = $db->query($sql);
    $result = $result->fetchArray(SQLITE3_ASSOC);

    $fields = array();
    $fields["departmentID"] = $result["ID"];
    $fields["yearID"] = $yearID;
    $fields["number"] = "1001";
    $fields["endNumber"] = "4999";
    $fields["title"] = "Transfer Credit";
    $fields["linkid"] = "";
    $fields["offered"] = 3;
    $fields["years"] = 3;
    $fields["hours"] = -1;
    $db->insert("classes", $fields);
}
//die();

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
//    $yearArray["updated"] += 1;
//    $db->update("years", $yearArray, array("ID"=>$yearID));
?>

<?php
//    print "starting normal cache...<br>\n";
    $db->close();
    require_once("cache.php");
?>