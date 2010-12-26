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

    require_once($path."ClassList.php");
    require_once($path."Course.php");

    class Semester {
        //6 years with no summers
        public static $CARDINAL_STRINGS = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth", "Eleventh", "Twelth");

        public static $SEMESTERS = array("Spring", "Summer", "Fall");
        const SPRING = 0;
        const SUMMER = 1;
        const FALL = 2;

        protected $classes;
        protected $completedHours = 0;
        protected $hours = 0;
        protected $order;
        protected $year;
        protected $semesterID;

        protected function __construct(array $classes=array(), $order) {
            $this->classes = new ClassList();
            array_walk($classes, array($this, "addClass"));
            $this->order = $order;
        }

        public function addClass(Course $class) {
            $this->classes[$class->getID()] = $class;
            $this->hours += $class->getHours();
            if($class->isComplete()) {
                $this->completedHours += $class->getHours();
            }
        }

        public function completeClass(Course $class, Course $completingClass) {
            $class->setComplete($completingClass);
            $this->completedHours += $class->getHours();
        }

        public function display($catalogYear) {

        }

        public function initEvalTaken(ClassList $classes, $user, $notes=null) {
            $map["userID"] = $user;
            if(empty($notes)) {
                //Identical classes must be valid substitutes. Seeing as they're identical...
                foreach($this->classes as $key=>$class) {
                    if(isset($classes[$key])) {
                        substituteClass($user, $class->getID(), $classes[$key]->getID());
                        $map["oldClassID"] = $class->getID();
                        $map["newClassID"] = $classes[$key]->getID();
                        SQLiteManager::getInstance()->insert("userClassMap", $map);
                        $this->completeClass($class, $classes[$key]);
                    }
                }
            } else {
                foreach($classes as $key=>$class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    foreach($this->classes as $class) {
                        if($class->isComplete()) {
                            continue;
                        }
                        $note = $notes->getNote($class->getNoteID());
                        if(!empty($note) && preg_match("/(\w{4})\s*(\d{4}).*(\w{4})\s*(\d{4})/isS", $note, $matches)) {
                            //explicit course substitution
                            if($class2->getDepartment() == $matches[1] && $class2->getNumber() == $matches[2]) {
                                substituteClass($user, $class->getID(), $class2->getID());
                                $this->completeClass($class, $class2);
                                break;
                            }
                        }
                        if($class->getNumber()) {
                            //title & department matching (they change the middle two number sometimes, seemingly for no good reason...
                            if($class->getDepartment() == $class2->getDepartment() && $class->getTitle() == $class2->getTitle()) {
                                substituteClass($user, $class->getID(), $class2->getID());
                                $this->completeClass($class, $class2);
                                break;
                            }
                        }
                        if($class->getDepartment() == $class2->getDepartment() && $class->getHours() <= $class2->getHours()) {
                            substituteClass($user, $class->getID(), $class2->getID());
                            $this->completeClass($class, $class2);
                            break;
                        }
                    }
                }
            }
        }

        public function evalTaken(ClassList $classes, ClassList $mapping) {
            foreach($this->classes as $old=>$class) {
                if(isset($mapping[$old])) {
                    $new = $mapping[$old];
					print "$old -> $new<br>";
					print "class = $class<br>";
					print "class = ".$classes[$new]."<br>";
                    $this->completeClass($class, $classes[$new]);
                    unset($mapping[$old]);
                    if(!($classes[$new]->getDepartment() == "LETU" && $classes[$new]->getNumber() == "4999")) {
                        unset($classes[$new]);
                    }
                }
            }
        }

        public function getCompletedHours() {
            return $this->completedHours;
        }

        public function getClasses() {
            return $this->classes;
        }

        static function getFromDegree($degreeID, $semester, Notes $notes) {
            $db = SQLiteManager::getInstance();
            $sql = "SELECT courseID, notes
                       FROM degreeCourseMap
                       WHERE degreeID=".$degreeID." AND semester=".$semester;
            $result = $db->query($sql);
            $classes = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $class = Course::getFromID($row["courseID"]);
                if(!empty($row["notes"])) {
                    $class->setNoteID($notes->add($row["notes"]));
                }
                $classes[] = $class;
            }
            return new Semester($classes, $semester);
        }

        public function getHours() {
            return $this->hours;
        }

        public function getID() {
            return $this->semesterID;
        }

        public function getIncompleteClasses() {
            $ret = new ClassList();
            foreach($this->classes as $class) {
                if(!$class->isComplete()) {
                    $ret[$class->getID()] = $class;
                }
            }
            return $ret;
        }

        public function getYear() {
            return $this->year;
        }

        public function hasClass(Course $class) {
            return isset($this->classes[$class->getID()]);
        }

        public function removeClass($id) {
            $class = $this->classes[$id];
            $this->hours -= $class->getHours();
            if($class->isComplete()) {
                $this->completedHours -= $class->getHours();
            }
            unset($this->classes[$id]);
        }

        public function setSemester($semesterID) {
            $this->semesterID = $semesterID;
        }

        public function setYear($year) {
            $this->year = $year;
        }
    }
?>