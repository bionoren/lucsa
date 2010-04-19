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

    require_once("SQLiteManager.php");
    require_once("functions.php");

    $db = SQLiteManager::getInstance();
    $departmentLookup = array();
    $result = $db->query("SELECT ID,department FROM departments");
    while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $departmentLookup[$row["department"]] = $row["ID"];
    }

    $result = $db->query("SELECT * FROM years");// WHERE updated&2=0");
//    $yearArray = $result->fetchArray(SQLITE3_ASSOC);
//    $year = $yearArray["year"];
//    $yearID = $yearArray["ID"];
while($row = $result->fetchArray()) {
    $year = $row["year"];
    $yearID = $row["ID"];

    $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year);
    $key += 1;
    $matches = array();
    preg_match("/\<select[^\>]*?degree.*?\>.*?\<\/select\>/is", $data, $matches);
    $parts = preg_split("/\<option[^\>]*?value=.\D+./is", $matches[0]);
    preg_match_all("/\<[^\>]*?(\d{4}).*?\>(.*?)\</is", $parts[1], $matches, PREG_SET_ORDER);
    $majors = array();
    foreach($matches as $match) {
        $majors[$match[1]][0] = $match[2];
        $fields = array();
        $fields["yearID"] = $key;
        $fields["linkid"] = $match[1];
        $fields["name"] = $match[2];
        $fields["type"] = 1;
        $db->insert("degrees", $fields);
        $majors[$match[1]][1] = $db->getLastInsertID();
    }
    $matches = array();
    preg_match_all("/\<[^\>]*?(\d{4}).*?\>(.*?)\</is", $parts[2], $matches, PREG_SET_ORDER);
    $minors = array();
    foreach($matches as $match) {
        $minors[$match[1]][0] = $match[2];
        $fields = array();
        $fields["yearID"] = $key;
        $fields["linkid"] = $match[1];
        $fields["name"] = $match[2];
        $fields["type"] = 2;
        $db->insert("degrees", $fields);
        $minors[$match[1]][1] = $db->getLastInsertID();
    }

//    goto minors;
    foreach($majors as $majorID=>$arr) {
        $major = $arr[0];
//        print "--Evaluating major $major for year $year<br>";
        $key2 = $arr[1];
        $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&degree=".$majorID);
        $matches = array();
        preg_match("/majorTitle.*?\((.*?)\)/is", $data, $matches);
        $db->update("degrees", array("acronym"=>$matches[1]), array("ROWID"=>$key2));

        $data = preg_replace("/^.*?\<\/td\>\<\/tr\>\<tr\>\<td\>.*?table.*?\>\<tr\>\<td.*?\>/is", "", $data);
        $data = preg_replace("/\<div.*?\>Total.*/is", "", $data);
        $sems = preg_split("/\<\/table.*?\<table.*?\<table.*?\>\<\/table.*?\>/is", "</table<table".$data);
        array_shift($sems);
        $i = 1;
        foreach($sems as $sem) {
            $classes = array();
            preg_match_all("/\>(\w{4}|&nbsp;).*?\>(\d{4}|&nbsp;).*?\<a.*?href=(\"|').*?(\d+)\\3.*?\>\s*(.*?)\s*(?:\(\s*L\s*\)\s*)?\<.*?\<span.*?extra.*?\>\s*(.*?)\s*\<.*?(?:acronym.*?title=(\"|')(.*?)\\7.*?)?\<\/td\>\<\/tr\>\<\/tr\>/is", $sem, $classes, PREG_SET_ORDER);
            foreach($classes as $class) {
                if($class[5] == "Fulfill English Proficiency Requirement") {
                    continue;
                }
//                print_r($class);
//                print "<br>";
//                continue;
                $dept = $departmentLookup[$class[1]];
                if($class[2] == "&nbsp;") {
                    $class[2] = "";
                }
                $num = $class[2];
                $sql = "SELECT ID,departmentID FROM classes WHERE yearID=".$yearID." AND title='".SQLite3::escapeString($class[5])."'";
                if(!empty($dept)) {
                    $sql .= " AND departmentID=".$dept;
                }
                $result2 = $db->query($sql);
                if($row = $result2->fetchArray()) {
                    $classID = $row["ID"];
                } else {
                    $fields = array();
                    if(empty($dept)) {
                        $dept = $row["departmentID"];
                    }
                    if(!empty($dept)) {
                        $fields["departmentID"] = $dept;
                    }
                    $fields["yearID"] = $yearID;
                    $fields["title"] = $class[5];
                    $fields["linkid"] = $class[4];
                    if(!empty($class[6])) {
                        $matches = array();
                        if(stristr($class[6], "only") !== false) {
                            if(stristr($class[6], "spring") !== false) {
                                $fields["offered"] = 1;
                            } else {
                                $fields["offered"] = 2;
                            }
                            if(stristr($class[6], "odd") !== false) {
                                $fields["years"] = 1;
                            } elseif(stristr($class[6], "even") !== false) {
                                $fields["years"] = 2;
                            } else {
                                $fields["years"] = 3;
                            }
                            $matches = array();
                        }
                        if(preg_match("/(\d+).*hour/is", $class[6], $matches) == 1) {
                            $fields["hours"] = $matches[1];
                        }
                    }
                    $db->insert("classes", $fields, true);
                    $classID = $db->getLastInsertID();
                }
                /*
                $fields[] = new DBField("departmentID", DBField::NUM, "-1", "departments", "ID");
                $fields[] = new DBField("number", DBField::NUM);
                $fields[] = new DBField("title", DBField::STRING);
                $fields[] = new DBField("linkid", DBField::NUM);
                $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
                $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
                $fields[] = new DBField("hours", DBField::NUM, 3);
                $db->createTable("classes", $fields);
                */

                $fields = array();
                $fields["degreeID"] = trim($key2);
                $fields["courseID"] = $classID;
                $fields["semester"] = $i;
                if(!empty($class[8])) {
                    $fields["notes"] = trim($class[8]);
                }
                $db->insert("degreeCourseMap", $fields);
            }
            $i++;
        }
        $db->update("degrees", array("numSemesters"=>($i-1)), array("ROWID"=>$key2));
    }
    minors:
    foreach($minors as $minorID=>$arr) {
        $minor = $arr[0];
//        print "--Evaluating minor $minor for year $year<br>";
        $key2 = $arr[1];
        $data = getCache("http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year=".$year."&degree=".$minorID);
        $matches = array();
        preg_match("/majorTitle.*?\((.*?)\)/is", $data, $matches);
        $db->update("degrees", array("acronym"=>$matches[1]), array("ROWID"=>$key2));
    }
//break;
}
//    $yearArray["updated"] += 2;
//    $db->update("years", $yearArray, array("ID"=>$yearID));
?>