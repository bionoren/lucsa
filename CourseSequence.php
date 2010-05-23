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
        protected $acronym;
        protected $linkid;
        protected $name;
        protected $type;
        protected $semesters;
        protected $year;

        public function __construct(array $row) {
            $this->year = $row["year"];
            $this->linkid = $row["linkid"];
            $this->name = $row["name"];
            $this->acronym = $row["acronym"];
            $this->type = $row["type"]; //none, major, minor
            for($i = 1; $i <= $row["numSemesters"]; $i++) {
                $this->semesters[] = Semester::getFromDegree($row["ID"], $i);
            }
        }

        //call this before merging in majors or minors
        //because elective substitutions will need to be cleared
        public function clearTaken() {
        }

        public function display() {
            print '<table>';
                print '<tr>';
                    print '<td colspan=2 class="majorTitle">';
                        print $this->name.' ('.$this->acronym.')';
                        print '<br>';
                        print '<span class="sequenceTitle">Sequence Sheet for '.$this->year.'-'.($this->year+1).'</span>';
                    print '</td>';
                print '</tr>';
                print '<tr>';
                    $i = 0;
                    $year = $this->getYear();
                    $totalHours = 0;
                    $hoursCompleted = 0;
                    $notes = array();
                    foreach($this->semesters as $semester) {
                        $semester->display($year, $year, $i, $notes);
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
                if(!empty($notes)) {
                    print '<tr>';
                        print '<td colspan="2" class="endNote">';
                        print 'Notes:';
                        foreach($notes as $i=>$note) {
                            print '<br><span class="endNote">'.($i+1).'</span>';
                            print ": ".$note;
                        }
                        print '</td>';
                    print '</tr>';
                }
            print '</table>';
        }

        public function evalTaken(ClassList $classesTaken, $user) {
            $db = SQLiteManager::getInstance();
            //do direct subsitutions first. This needs to be an entirely seperate pass
            foreach($this->semesters as $key=>$semester) {
                $semester->evalTaken($classesTaken);
            }
            //evaluate user-defined substitutions and substitutions from notes
            //need to translate classTaken department and number keys into a DB class key
            $mapping = array();
            $result = $db->query("SELECT oldClassID, newClassID FROM userClassMap WHERE userID=".$user);
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $mapping[$row["oldClassID"]] = $row["newClassID"];
            }
            foreach($this->semesters as $semester) {
                $semester->evalTaken($classesTaken, $mapping);
            }
        }

        public static function getFromID($id) {
            $db = SQLiteManager::getInstance();
            $sql = "SELECT degrees.*, years.year
                    FROM degrees
                    JOIN years ON degrees.yearID=years.ID
                    WHERE degrees.ID=".$id;
            $result = $db->query($sql);
            return new CourseSequence($result->fetchArray(SQLITE3_ASSOC));
        }

        public function getIncompleteClasses() {
            $ret = array();
            foreach($this->semesters as $sem) {
                $ret = array_merge($ret, $sem->getIncompleteClasses());
            }
            return $ret;
        }

        protected function getYear() {
            return $this->year;
        }
    }
?>