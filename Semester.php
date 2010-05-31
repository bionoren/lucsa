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

    require_once("ClassList.php");
    require_once("Course.php");

    class Semester {
        public static $CARDINAL_STRINGS = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth");

        protected $classes;
        protected $completedHours = 0;
        protected $hours = 0;

        protected function __construct(array $classes=array()) {
            $this->classes = new ClassList();
            array_walk($classes, array($this, "addClass"));
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

        public function display($catalogYear, $year, $semester, &$notes) {
            print '<td valign="top">';
                print '<table style="width:100%;">';
                    print '<tr class="noborder">';
                        print '<td colspan="3">';
                            print '<table width="100%" class="semesterHeader">';
                                print '<tr>';
                                    print '<td class="semesterTitle';
                                    if($this->completedHours == $this->hours) {
                                        print ' strike';
                                    }
                                    print '">';
                                        print Semester::$CARDINAL_STRINGS[$semester].' Semester - ';
                                        print ($semester % 2 == 0)?"Fall":"Spring";
                                        print " ".$year;
                                    print '</td>';
                                    print '<td class="semesterHours';
                                    if($this->completedHours == $this->hours) {
                                        print ' strike';
                                    }
                                    print '">';
                                        print $this->getHours().' hours';
                                    print '</td>';
                                print '</tr>';
                            print '</table>';
                        print '</td>';
                    print '</tr>';
                    foreach($this->classes as $class) {
                        $class->display($catalogYear, $notes);
                    }
                print '</table>';
            print '</td>';
        }

        public function evalTaken(ClassList $classes, $mapping=null) {
            if($mapping === null) {
                //Identical classes must be valid substitutes. Seeing as they're identical...
                foreach($this->classes as $key=>$class) {
                    if(isset($classes[$key])) {
                        $this->completeClass($class, $classes[$key]);
                        unset($classes[$key]);
                    }
                }
            } else {
                //elective evaluation + basic subsititution attempts (ie from notes)
                //also, user-defined substitutions via $mapping
                //always evaluate user mappings first
                foreach($mapping as $old=>$new) {
                    if(isset($this->classes[$old]) && isset($classes[$new])) {
                        $this->completeClass($this->classes[$old], $classes[$new]);
                        unset($classes[$new]);
                    }
                }
                foreach($this->classes as $class) {
                    if($class->isComplete()) {
                        continue;
                    }
                    $notes = $class->getNotes();
                    if(!empty($notes) && preg_match("/(\w{4})\s*(\d{4}).*(\w{4})\s*(\d{4})/isS", $notes, $matches)) {
                        //explicit course substitution
                        foreach($classes as $key=>$class2) {
                            if($class2->getDepartment() == $matches[1] && $class2->getNumber() == $matches[2]) {
                                $this->completeClass($class, $class2);
                                unset($classes[$key]);
                                break;
                            }
                        }
                    } elseif(!$class->getNumber()) {
                        //elective substitution
                        foreach($classes as $key=>$class2) {
                            if($class->getDepartment() == $class2->getDepartment() && $class2->getHours() >= $class->getHours()) {
                                $this->completeClass($class, $class2);
                                unset($classes[$key]);
                                break;
                            }
                        }
                    }
                }
            }
        }

        public function getCompletedHours() {
            return $this->completedHours;
        }

        static function getFromDegree($degreeID, $semester) {
            $db = SQLiteManager::getInstance();
            $sql = "SELECT courseID, notes
                       FROM degreeCourseMap
                       WHERE degreeID=".$degreeID." AND semester=".$semester;
            $result = $db->query($sql);
            $classes = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $class = Course::getFromID($row["courseID"]);
                $class->setNotes($row["notes"]);
                $classes[] = $class;
            }
            return new Semester($classes);
        }

        public function getHours() {
            return $this->hours;
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
    }
?>