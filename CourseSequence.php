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

    require_once($path."Semester.php");

    class CourseSequence {
        protected $id;
        protected $acronym;
        protected $linkid;
        protected $name;
        protected $type;
        protected $semesters;
        protected $year;
        protected $notes;
        protected $hours;
        protected $completeHours;

        public function __construct(array $row) {
            $this->id = $row["ID"];
            $this->year = $row["year"];
            $this->linkid = $row["linkid"];
            $this->name = $row["name"];
            $this->acronym = $row["acronym"];
            $this->type = $row["type"]; //none, major, minor
            $this->notes = new Notes();
            $year = $this->year;
            $semNum = Semester::FALL;
            for($i = 1; $i <= $row["numSemesters"]; $i++) {
                $tmp = Semester::getFromDegree($this->id, $i, $this->notes);
                $tmp->setYear($year);
                $tmp->setSemester($semNum++%count(Semester::$SEMESTERS));
                if($semNum%count(Semester::$SEMESTERS) == Semester::SUMMER) {
                    $semNum++;
                }
                if($semNum%count(Semester::$SEMESTERS) == Semester::SPRING) {
                    $year++;
                }
                $this->hours += $tmp->getHours();
                $this->semesters[] = $tmp;
            }
        }

		public function applySubstitions(ClassList $classesTaken, $user) {
			$mapping = CourseSequence::getUserClassMap($user);
			$mapping->sort();
            foreach($this->semesters as $semester) {
                $semester->evalTaken($classesTaken, $mapping);
                $this->completeHours += $semester->getCompletedHours();
            }
        }

		public function autocompleteClasses(ClassList $classes, $user) {
			foreach($this->semesters as $semester) {
				$semester->initEvalTaken($classes, $user);
			}
			foreach($this->semesters as $semester) {
				$semester->initEvalTaken($classes, $user, $this->notes);
                $this->completeHours += $semester->getCompletedHours();
			}
		}

		protected static function getUserClassMap($user) {
			$db = SQLiteManager::getInstance();
            //evaluate user-defined substitutions and substitutions from notes
            //need to translate classTaken department and number keys into a DB class key
            $ret = new ClassList();
            $result = $db->query("SELECT oldClassID, newClassID FROM userClassMap WHERE userID=".$user);
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ret[$row["oldClassID"]] = $row["newClassID"];
            }
			return $ret;
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

        public function getAcronym() {
            return $this->acronym;
        }

        public function getCompletedHours() {
            return $this->completeHours;
        }

        public function getHours() {
            return $this->hours;
        }

        public function getID() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getNotes() {
            return $this->notes;
        }

        public function getSemesters() {
            return $this->semesters;
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