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
    					print $name."[".$key."] = ".htmlentities($val)."<br/>\n";
                    } else {
                        print $name."[".$key."] = ".htmlentities($val->{$member}())."<br/>\n";
                    }
                }
			}
		}
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

    function displayCourseSequence(SQLiteManager $db, $startYear, array $allCourses, array $courses) {
        $year = $startYear;
        $numStrs = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth");
        $sem = $allCourses[0]["semester"];
        $semesters = array();
        $i = 0;
        $hours = 0;
        $allClasses = array();
        foreach($allCourses as $course) {
            if(empty($course["department"])) {
                continue;
            }
            if($course["semester"] != $sem) {
                array_unshift($semesters[$i], $hours);
                $i++;
                $sem = $course["semester"];
                $hours = 0;
            }
            $semesters[$i][] = $course;
            $allClasses[$coures["department"].$course["number"]] = $course;
            $hours += $course["hours"];
        }
        array_unshift($semesters[$i], $hours);
        print '<table>';
            print '<tr>';
            $totalHours = 0;
            $hoursCompleted = 0;
            $notes = array();
            for($i = 0; $i < count($semesters); $i++) {
                $hours = $semesters[$i][0];
                $totalHours += $hours;
                print '<td valign="top">';
                    print '<table style="width:100%;">';
                        print '<tr class="noborder">';
                            print '<td colspan="3">';
                                print '<table width="100%" class="semesterHeader">';
                                    print '<tr>';
                                        print '<td class="semesterTitle">';
                                            print $numStrs[$i].' Semester - ';
                                            print ($i % 2 == 0)?"Fall":"Spring";
                                            print " ".$year;
                                        print '</td>';
                                        print '<td class="semesterHours">';
                                            print $hours.' hours';
                                        print '</td>';
                                    print '</tr>';
                                print '</table>';
                            print '</td>';
                        print '</tr>';
                        foreach($semesters[$i] as $class) {
                            $taken = false;
                            $alt = null;
                            if(isset($courses[$class["department"]][$class["number"]])) {
                                $taken = true;
                                $hoursCompleted += $class["hours"];
                                unset($courses[$class["department"]][$class["number"]]);
                            } elseif(empty($class["number"]) && isset($courses[$class["department"]])) {
                                //look through all the classes we've taken
                                foreach($courses[$class["department"]] as $key=>$course) {
                                    //if we've taken one in this department that's not being used for anything,
                                    //it's probably being used for one of these electives
                                    if(!isset($allClasses[$class["department"].$key]) && substr($key, -1) == $class["hours"]) {
                                        $taken = true;
                                        $hoursCompleted += $class["hours"];
                                        $alt = $courses[$class["department"]][$key];
                                        unset($courses[$class["department"]][$key]);
                                        break;
                                    }
                                }
                            }
                            print '<tr class="course">';
                                print '<td ';
                                if($taken) {
                                    print 'class="strike" ';
                                }
                                print 'style="width:0px;">';
                                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$startYear.'&school='.$class["departmentid"].'&cmd=courselist">';
                                        print $class["department"];
                                    print '</a>';
                                print '</td>';
                                print '<td ';
                                if($taken) {
                                    print 'class="strike" ';
                                }
                                print 'style="width:0px;">';
                                    print $class["number"];
                                print '</td>';
                                print '<td';
                                if($taken) {
                                    print ' class="strike"';
                                }
                                print '>';
                                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$startYear.'&course='.$class["id"].'">';
                                        print $class["title"];
                                    print '</a>';
                                    if(empty($class["number"])) {
                                        print '<span class="note">';
                                            print " (".$class["hours"]." hour";
                                            if($class["hours"] != 1) {
                                                print "s";
                                            }
                                            print ")";
                                        print '</span>';
                                    }
                                    if($class["offered"] < 3 || $class["years"] < 3) {
                                        print '<span class="note">';
                                            print " (";
                                            if($class["offered"] < 3) {
                                                if($class["offered"] == 1) {
                                                    print "Spring";
                                                } else {
                                                    print "Fall";
                                                }
                                                if($class["years"] < 3) {
                                                    print ",";
                                                }
                                                print " ";
                                            }
                                            if($class["years"] < 3) {
                                                if($class["years"] == 1) {
                                                    print "Odd years";
                                                } else {
                                                    print "Even years";
                                                }
                                            }
                                            print " only";
                                            print ")";
                                        print '</span>';
                                    }
                                    if(!empty($class["extra"])) {
                                        $key = array_search($class["extra"], $notes);
                                        if($key === false) {
                                            $key = count($notes);
                                            $notes[] = $class["extra"];
                                        }
                                        print '<span class="footnote">';
                                            print " (".($key+1).")";
                                        print '</span>';
                                    }
                                    if(isset($alt)) {
                                        print '<br>'.$alt;
                                    }
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
            print '<tr>';
                print '<td colspan="2" align="center">';
                    print "Completed Hours: ".$hoursCompleted."<br>";
                    print "Remaining Hours: ".($totalHours-$hoursCompleted)."<br>";
                    print "Total Hours: ".$totalHours;
                print '</td>';
            print '</tr>';
            print '<tr>';
                print '<td colspan="2" class="endNote">';
                print 'Notes:<br>';
                for($i = 0; $i < count($notes); $i++) {
                    print '<span class="endNote">'.($i+1).'</span>';
                    print ": ".$notes[$i];
                    if($i+1 < count($notes)) {
                        print "<br>";
                    }
                }
                print '</td>';
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
        $result = $db->query("SELECT ID,year from years");
        $years = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $years[$row["ID"]] = $row["year"];
        }
        return $years;
    }

    function getMajors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='1'");
        $majors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $majors[$row["acronym"]] = $row;
        }
        return $majors;
    }

    function getMinors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".($year+1)."' AND type='2'");
        $minors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $minors[$row["acronym"]] = $row;
        }
        return $minors;
    }

    function getCourses(SQLiteManager $db, array $degrees) {
        $ret = array();
        foreach($degrees as $degree) {
            $sql = "SELECT degreeCourseMap.semester, degreeCourseMap.notes, classes.number, classes.title, classes.linkid,
                    classes.offered, classes.years, classes.hours, years.year, departments.department, departments.linkid AS deptlinkid
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