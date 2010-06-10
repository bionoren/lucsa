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
        $result = $db->query("SELECT ID,year FROM years ORDER BY year DESC");
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
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='2'");
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

    /**
     * Gets the user's ID either from the session or from database.
     *
     * @param SQLiteManager $db Database connection object.
     * @return INTEGER The user's ID.
     */
    function getUserID(SQLiteManager $db) {
        if(!empty($_SESSION["userID"])) {
            $userID = $_SESSION["userID"];
        } else {
            if(isset($_SERVER['PHP_AUTH_USER'])) {
                //we need the name to be consistent
                $name = encrypt($_SERVER["PHP_AUTH_USER"], md5($_SERVER["PHP_AUTH_USER"]));
                $result = $db->query("SELECT ID FROM users WHERE user='".$name."'");
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if($row != false) {
                    $userID = $_SESSION["userID"] = $row["ID"];
                } else {
                    $db->insert("users", array("user"=>$name, "salt"=>$salt));
                    $userID = $_SESSION["userID"] = $db->getLastInsertID();
                }
                unset($salt);
                unset($name);
            }
        }

        return $userID;
    }

    /**
     * Substitutes one class for another in the database.
     *
     * Deletes any previous substitutions on this class. If $subID is null, simply removes
     * any existing substitutions on the old class.
     *
     * @param SQLiteManager $db Database connection object.
     * @param INTEGER $userID The ID of the user.
     * @param INTEGER $origID The ID of the original class.
     * @param INTEGER $subID The ID of the class to substitute (if any).
     * @return VOID
     */
    function substituteClass(SQLiteManager $db, $userID, $origID, $subID=null) {
        $sql = "DELETE FROM userClassMap WHERE userID=".$userID." AND oldClassID=".$origID;
        $db->query($sql);

        if(!empty($subID)) {
            $fields["userID"] = $userID;
            $fields["oldClassID"] = $origID;
            $fields["newClassID"] = $subID;
            $db->insert("userClassMap", $fields);
        }
    }

    function getKeyStr() {
        return "Curse:DuckInADungeon. You should know better than to pick up a duck in a dungeon.";
    }

    function getQS() {
        if(!empty($_GET)) {
            $ret = "?";
        }
        foreach($_GET as $key=>$val) {
            if(is_array($val)) {
                foreach($val as $val2) {
                    $ret .= $key."[]=".$val2."&";
                }
            } else {
                $ret .= $key."=".$val."&";
            }
        }
        return substr($ret, 0, -1);
    }
?>