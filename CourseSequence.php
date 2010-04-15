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

    require_once("Semester.php");

    class CourseSequence {
        protected $semesters;
        protected $year;
        protected $linkid;
        protected $name;
        protected $acronym;
        protected $type;

        public function __construct(SQLiteManager $db, array $row) {
            $this->year = $row["year"];
            $this->linkid = $row["linkid"];
            $this->name = $row["name"];
            $this->acronym = $row["acronym"];
            $this->type = $row["type"]; //none, major, minor
            for($i = 1; $i <= $row["numSemesters"]; $i++) {
                $this->semesters[] = Semester::getFromDegree($db, $row["ID"], $i);
            }
        }

        public function getFromID(SQLiteManager $db, $id) {
            $sql = "SELECT degrees.*, years.year
                    FROM degrees
                    JOIN years ON degrees.yearID=years.ID
                    WHERE degrees.ID=".$id;
            $result = $db->query($sql);
            return new CourseSequence($db, $result->fetchArray(SQLITE3_ASSOC));
        }

        public function evalTaken(SQLiteManager $db, array $classesTaken) {
            //do direct subsitutions first
            foreach($this->semesters as $semester) {
                $semester->evalTaken($classesTaken);
            }
            //evaluate user-defined substitutions and substitutions from notes
            //need to translate classTaken department and number keys into a DB class key
            $mapping = array();
            $result = $db->query("SELECT oldClassID, newClassID FROM userClassMap WHERE userID=".$_SESSION["userID"]);
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $mapping[$row["oldClassID"]] = $row["newClassID"];
            }
            foreach($this->semesters as $semester) {
                $semester->evalTaken($classesTaken, $mapping);
            }
        }

        //call this before merging in majors or minors
        public function clearTaken() {
        }

        public function display() {
            print '<table>';
                print '<tr>';
                    $i = 0;
                    $year = $this->year;
                    $totalHours = 0;
                    $hoursCompleted = 0;
                    $notes = array();
                    foreach($this->semesters as $semester) {
                        $semester->display($this->year, $year, $i, $notes);
                        $totalHours += $semester->getHours();
                        $hoursCompleted += $semester->getCompletedHours();
                        if($i++ % 2 == 1) {
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
    }

/*    $taken = false;
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
    } elseif(!empty($class["notes"]) && strstr($class["notes"], "substituted") && isset($courses[$class["department"]])) {
        $matches = array();
        //try to find a simple course substitution from footnote
        if(preg_match("/(\w{4})\s*(\d{4}).*?substituted.*?(\w{4})\s*(\d{4})/is", $class["notes"], $matches)) {
            if($matches[3] == $class["department"] && $matches[4] == $class["number"]
                && isset($courses[$matches[1]][$matches[2]])) {
                $taken = true;
                $hoursCompleted += $class["hours"];
                $alt = $courses[$matches[1]][$matches[2]];
                unset($courses[$matches[1]][$matches[2]]);
            }
        }
    }*/
?>