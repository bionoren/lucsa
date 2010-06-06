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
        protected $notes;

        public function __construct(array $row) {
            $this->year = $row["year"];
            $this->linkid = $row["linkid"];
            $this->name = $row["name"];
            $this->acronym = $row["acronym"];
            $this->type = $row["type"]; //none, major, minor
            $this->notes = new Notes();
            $year = $this->year;
            $semNum = Semester::FALL;
            for($i = 1; $i <= $row["numSemesters"]; $i++) {
                $tmp = Semester::getFromDegree($row["ID"], $i, $this->notes);
                $tmp->setYear($year);
                $tmp->setSemester($semNum++%count(Semester::$SEMESTERS));
                if($semNum%count(Semester::$SEMESTERS) == Semester::SUMMER) {
                    $semNum++;
                }
                if($semNum%count(Semester::$SEMESTERS) == Semester::SPRING) {
                    $year++;
                }
                $this->semesters[] = $tmp;
            }
        }

        //call this before merging in majors or minors
        //because elective substitutions will need to be cleared
        public function clearTaken() {
        }

        public function display() {
            $totalHours = 0;
            $hoursCompleted = 0;
            print '<table>';
                $this->displayHeader();
                foreach($this->semesters as $semester) {
                    print '<td valign="top">';
                        $semester->display($this->getYear());
                        $totalHours += $semester->getHours();
                        $hoursCompleted += $semester->getCompletedHours();
                        if($semester->getSemesterID() == Semester::SPRING) {
                            print '</tr><tr>';
                        }
                    print '</td>';
                }
                $this->displayFooter($hoursCompleted, $totalHours);
            print '</table>';
        }

        protected function displayHeader() {
            print '<tr>';
                print '<td colspan=2 class="majorTitle">';
                    print $this->name.' ('.$this->acronym.')';
                    print '<br>';
                    print '<span class="sequenceTitle">Sequence Sheet for '.$this->year.'-'.($this->year+1).'</span>';
                    print '<br>';
                    $dispVarSave = $_GET["disp"];
                    $_GET["disp"] = "%s";
                    print '<span class="sequenceLinks">
                        <a href="'.sprintf(getQS(), "list").'">Requirements List</a>
                        - <a href="'.sprintf(getQS(), "detail").'">Detail View</a>
                        - <a href="'.sprintf(getQS(), "summary").'">Summary View</a>';
                    print '</span>';
                    $_GET["disp"] = $dispVarSave;
                    print '<br style="vertical-align:top; line-height:28px;">';
                print '</td>';
            print '</tr>';
        }

        protected function displayFooter($hoursCompleted, $totalHours) {
            print '<tr>';
                print '<td colspan="2" align="center">';
                    print "Completed Hours: ".$hoursCompleted."<br>";
                    print "Remaining Hours: ".($totalHours-$hoursCompleted)."<br>";
                    print "Total Hours: ".$totalHours;
                print '</td>';
            print '</tr>';
            if($this->notes->count() > 0) {
                print '<tr>';
                    print '<td colspan="3" class="endNote">';
                    print 'Notes:';
                    foreach($this->notes->getNotes() as $i=>$note) {
                        print '<br><span class="endNote">'.$i.'</span>';
                        print ": ".$note;
                    }
                    print '</td>';
                print '</tr>';
            }
        }

        public function displayRequirementsList() {
            $totalHours = 0;
            $hoursCompleted = 0;
            print '<table>';
                $this->displayHeader();
                $allClasses = new ClassList();
                foreach($this->semesters as $semester) {
                    $allClasses = ClassList::merge($allClasses, $semester->getClasses());
                }
                $allClasses->sort();
                $i = 2;
                $count = floor($allClasses->count()/2);
                print '<tr style="vertical-align:top;">';
                    print '<td>';
                        print '<table>';
                            foreach($allClasses as $class) {
                                print $class->display($this->year);
                                $totalHours += $class->getHours();
                                if($class->isComplete()) {
                                    $hoursCompleted += $class->getHours();
                                }
                                if($i++ == $count) {
                                    print '</table></td><td><table>';
                                }
                            }
                        print '</table>';
                    print '</td>';
                print '</tr>';
                $this->displayFooter($hoursCompleted, $totalHours);
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
                $semester->evalTaken($classesTaken, $mapping, $this->notes);
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
            $ret = new ClassList();
            foreach($this->semesters as $sem) {
                $ret = ClassList::merge($ret, $sem->getIncompleteClasses());
            }
            return $ret;
        }

        protected function getYear() {
            return $this->year;
        }

        public function __toString() {
            return $this->name;
        }
    }

    class Notes implements Countable {
        protected $notes = array();

        function add($note) {
            $key = array_search($note, $this->notes);
            if($key === false) {
                $key = count($this->notes)+1;
                $this->notes[$key] = $note;
            }
            return $key;
        }

        function count() {
            return count($this->notes);
        }

        function getNote($id) {
            return $this->notes[$id];
        }

        function getNotes() {
            return $this->notes;
        }

        function __toString() {
            return "Notes";
        }
    }
?>