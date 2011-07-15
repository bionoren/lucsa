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
    $path = "./";
    unset($_SESSION["userID"]);
    session_write_close();
    require_once($path."functions.php");
    require_once($path."db/SQLiteManager.php");
    require_once($path."db/dbinit.php");

    $findSchoolsPattern = "/\<option[^\>]*?value=.(\d+)[^\>]*?\>(?:School|honors|LeTourneau).*?\</is";

    $departmentPattern = "/.majorTitle.+?>(.+?)\s*\((\w+)\)\s*\</is";

    $fetchSchoolsPattern = "/\<a[^\>]+?school=(\d+)[^\>]*?cmd=courselist.*?\>\s*\d{1,3}\D/is";

    $classSplitPattern = "/\<\/div\>.*?\<div.*?\>/is";

    $classParsePattern = "/\>\s*(?P<dept>[A-Z]{3,4})\s*(?P<num>\d{3,4}(-\d{3,4})?).*?\<a[^\>]*?course=(?P<linkid>\d+)[^\>]*\>\s*(?P<title>[^\<]+?)\s*\<";
    //try for prereq/coreq information...
    $classParsePattern .= ".*?\>\s*(?P<prereq>Prereq[^:C]+:\s*.+?)?(?P<coreq>Coreq[^:]+:\s*.+?)?(?P<either>Prereq[^:]+:\s*.+?)?";
    //...and times offered.
    $classParsePattern .= "(?:class=\"[^\"]*available[^\"]*\"[^\>]*\>\s*\(\s*(?P<time>[^\)]+)\s*\).*?)?$";
    //grap semester/year offering info if it exists
    $classParsePattern .= "/isS";

    $notAnchorTag = "/\<\/?[^a\/][^\>]*(\>|$)/is";

    $db = SQLiteManager::getInstance();
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
                    $db->insert("departments", $fields);
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

    //            dump("matches", $matches); continue;
                foreach($matches as $match) {
                    $fields = array();
                    $fields["departmentID"] = $deptID;
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
                    $db->insert("classes", $fields, true);
                    if($db->changed()) {
                        $classID = $db->getLastInsertID();
    //                    print "Added class ".$fields["title"]." - ".$fields["number"]."<br>";
                    } else {
                        $tmp = $db->select("classes", array("ID"), array_slice($fields, 0, 3, true))->fetchArray(SQLITE3_ASSOC);
                        $classID = $tmp["ID"];
                        if(empty($classID)) {
                            dump("fields", array_slice($fields, 0, 3, true));
                            dump("tmp", $tmp);
                            dump("number", $number);
                            print "year = $year<br>";
                        }
                    }

                    if(!empty($match["prereq"])) {
                        $data = SQLite3::escapeString(preg_replace($notAnchorTag, "", $match["prereq"]));
                        $db->query("INSERT OR REPLACE INTO classDependencyMap (classID, type, data) VALUES ($classID, 1, '$data')");
                    }
                    if(!empty($match["coreq"])) {
                        $data = SQLite3::escapeString(preg_replace($notAnchorTag, "", $match["coreq"]));
                        $db->query("INSERT OR REPLACE INTO classDependencyMap (classID, type, data) VALUES ($classID, 2, '$data')");
                    }
                    if(!empty($match["either"])) {
                        $data = SQLite3::escapeString(preg_replace($notAnchorTag, "", $match["either"]));
                        $db->query("INSERT OR REPLACE INTO classDependencyMap (classID, type, data) VALUES ($classID, 3, '$data')");
                    }
                }
            }
        }

        $sql = "SELECT ID FROM departments WHERE department='LETU'";
        $result = $db->query($sql);
        $result = $result->fetchArray(SQLITE3_ASSOC);

        //add transfer credit class
        $fields = array();
        $fields["departmentID"] = $result["ID"];
        $fields["number"] = "4991";
        $fields["endNumber"] = "4999";
        $fields["title"] = "Transfer Credit";
        $fields["linkid"] = "";
        $fields["offered"] = 3;
        $fields["years"] = 3;
        $fields["hours"] = -1;
        $db->insert("classes", $fields, true);
    }
?>

<?php
//    print "starting normal cache...<br>\n";
    unset($db);
    require_once("cache.php");

    SQLiteManager::getInstance()->query("ANALYZE");
?>