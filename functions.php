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

	//DEBUGGING FUNCTIONS
	function dump($name, $array, $member=null) {
		if(is_array($array) || (is_object($array) && $array instanceof Iterator)) {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".htmlentities($val)."<br/>\n";
                    } else {
                        print $name."[".$key."] = ".htmlentities($val->{$member}())."<br/>\n";
                    }
                }
			}
		} else {
            if($member == null) {
    			print "$name = ".htmlentities($array)."<br/>\n";
            } else {
                print "$name = ".htmlentities($array->{$member}())."<br/>\n";
            }
        }
	}

    //FUNCTIONS

    function displayClassSelect($name, ClassList $classes) {
        print '<select name="'.$name.'">';
            $classes->sort();
            $dept = null;
            foreach($classes as $class) {
                if($class->getDepartment() != $dept) {
                    $dept = $class->getDepartment();
                    print '<optgroup label="'.$dept.'">';
                }
                print '<option value="'.$class->getID().'"';
                if($class->isComplete()) {
                    print ' style="color:rgb(177, 177, 177);"';
                }
                print '>'.$class->getTitle().'</option>';
            }
        print '</select>';
    }

    function getCache($file) {
        $name = "cache/".md5($file).".tmp";
        if(file_exists($name)) {
            return file_get_contents($name);
        } else {
            $ret = file_get_contents($file);
            file_put_contents($name, $ret);
            return $ret;
        }
    }

    function guessMajor(array $majors, $major) {
        $min = 1000;
        $ret = "";
        foreach($majors as $key=>$mjr) {
            $try = levenshtein($major, $mjr["name"], 1, 6, 6);
            if($try < $min) {
                $min = $try;
                $ret = $key;
            }
        }
        return $ret;
    }

    function getYears() {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID,year from years");
        $years = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $years[$row["ID"]] = $row["year"];
        }
        return $years;
    }

    function getMajors($year) {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='1'");
        $majors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $majors[$row["acronym"]] = $row;
        }
        return $majors;
    }

    function getMinors($year) {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".($year+1)."' AND type='2'");
        $minors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $minors[$row["acronym"]] = $row;
        }
        return $minors;
    }

    function getCourses(array $degrees) {
        $db = SQLiteManager::getInstance();
        $ret = array();
        foreach($degrees as $degree) {
            $sql = "SELECT degreeCourseMap.semester, degreeCourseMap.notes,
                    classes.number, classes.title, classes.linkid, classes.offered, classes.years, classes.hours,
                    years.year,
                    departments.department, departments.linkid AS deptlinkid
                    FROM degreeCourseMap
                    JOIN classes ON degreeCourseMap.courseID=classes.ID
                    JOIN years ON classes.yearID=years.ID
                    JOIN departments ON classes.departmentID=departments.ID
                    WHERE degreeCourseMap.degreeID='".$degree["ID"]."' ORDER BY degreeCourseMap.semester";
            $result = $db->query($sql);
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ret[] = $row;
            }
        }
        return $ret;
    }

    function getKeyStr() {
        return "Curse:DuckInADungeon. You should know better than to pick up a duck in a dungeon.";
    }
?>