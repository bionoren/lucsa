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
		if(!is_array($array)) {
			print "$name = $array<br/>";
		} else {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".$val."<br/>";
                    } else {
                        print $name."[".$key."] = ".$val->{$member}()."<br/>";
                    }
                }
			}
		}
	}

    function displayCourseSequence($startYear, array $allCourses, array $courses) {
        $year = $startYear;
        $numStrs = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth");
        $sem = $allCourses[0]["semester"];
        $semesters = array();
        $i = 0;
        $hours = 0;
        foreach($allCourses as $course) {
            if($course["semester"] != $sem) {
                array_unshift($semesters[$i], $hours);
                $i++;
                $sem = $course["semester"];
                $hours = 0;
            }
            $semesters[$i][] = $course;
            $hours += $course["hours"];
        }
        array_unshift($semesters[$i], $hours);
        print '<table border="1">';
            print '<tr>';
            for($i = 0; $i < count($semesters); $i++) {
                $hours = $semesters[$i][0];
                print '<td valign="top">';
                    print '<table style="width:100%;">';
                        print '<tr>';
                            print '<td colspan="3">';
                                print '<table width="100%">';
                                    print '<tr>';
                                        print '<td style="text-align:left;">';
                                            print $numStrs[$i].' Semester - ';
                                            print ($i % 2 == 0)?"Fall":"Spring";
                                            print " ".$year;
                                        print '</td>';
                                        print '<td style="text-align:right;">';
                                            print $hours.' hours';
                                        print '</td>';
                                    print '</tr>';
                                print '</table>';
                            print '</td>';
                        print '</tr>';
                        for($j = 1; $j < count($semesters[$i]); $j++) {
                            $class = $semesters[$i][$j];
                            print '<tr>';
                                print '<td style="width:0px;">';
                                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$startYear.'&school='.$class["departmentid"].'&cmd=courselist">';
                                        print $class["department"];
                                    print '</a>';
                                print '</td>';
                                print '<td style="width:0px;">';
                                    print $class["number"];
                                print '</td>';
                                print '<td>';
                                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$startYear.'&course='.$class["id"].'">';
                                        print $class["title"];
                                    print '</a>';
                                print '</td>';
                            print '</tr>';
                        }
                    print '</table>';
                print '</td>';
                if($i % 2 == 1) {
                    print '</tr><tr>';
                } else {
                    $year++;
                }
            }
            print '</tr>';
        print '</table>';
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

    function getYears(SQLiteManager $db) {
        $result = $db->query("SELECT * from years");
        $years = array();
        while($years[] = $result->fetchArray(SQLITE3_NUM));
        array_pop($years);
        return $years;
    }

    function getMajors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT ROWID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='1'");
        $majors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $majors[$row["acronym"]] = $row;
        }
        return $majors;
    }

    function getMinors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT ROWID, name, acronym FROM degrees WHERE yearID='".($year+1)."' AND type='2'");
        $minors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $minors[$row["acronym"]] = $row;
        }
        return $minors;
    }

    function getCourses(SQLiteManager $db, array $degrees) {
        $ret = array();
        foreach($degrees as $degree) {
            $sql = "SELECT * FROM classes WHERE degreeID='".$degree["rowid"]."' ORDER BY semester";
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