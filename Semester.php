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

    require_once("Course.php");

    $cardinalNumberStrings = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth");

    class Semester {
        protected $classes = array();
        protected $hours = 0;
        protected $completedHours = 0;

        public function __construct(array $classes=null) {
            if($classes != null) {
                foreach($classes as $class) {
                    $this->addClass($class);
                }
            }
        }

        static function getFromDegree(SQLiteManager $db, $degreeID, $semester) {
            $sql = "SELECT courseID, notes
                       FROM degreeCourseMap
                       WHERE degreeID=".$degreeID." AND semester=".$semester;
//            die($sql);
            $result = $db->query($sql);
            $classes = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $class = Course::getFromID($db, $row["courseID"]);
                $class->setNotes($row["notes"]);
                $classes[] = $class;
            }
            return new Semester($classes);
        }

        public function addClass(Course $class) {
            $this->classes[$class->getID()] = $class;
            $this->hours += $class->getHours();
            if($class->isComplete()) {
                $this->completedHours += $class->getHours();
            }
        }

        public function hasClass(Course $class) {
            return isset($this->classes[$class->getID()]);
        }

        public function removeClass($id) {
            $this->hours -= $this->classes[$id]->getHours();
            if($this->classes[$id]->isComplete()) {
                $this->completedHours -= $this->classes[$id]->getHours();
            }
            unset($this->classes[$id]);
        }

        public function getHours() {
            return $this->hours;
        }

        public function getCompletedHours() {
            return $this->completedHours;
        }

        public function evalTaken(array &$classes, $mapping=null) {
            if($mapping === null) {
                //basic evaluation of course dept+number against course dept+number
//                print "##########################<br>";
//                dump("classes", $classes);
                foreach($classes as $key=>$class) {
//                    print $class->getID()."(".$class.") -> ".$this->classes[$class->getID()]."<br>";
                    if(isset($this->classes[$class->getID()])) {
                        $this->classes[$class->getID()]->setComplete($class);
                        unset($classes[$key]);
                        $this->completedHours += $class->getHours();
                    }
                }
            } else {
                //elective evaluation + basic subsititution attempts (ie from notes)
                //also, user-defined substitutions via $mapping
//                dump("classes", $classes);
//                dump("mapping", $mapping);
                foreach($this->classes as $class) {
                    $notes = $class->getNotes();
                    if(!empty($notes)) {
                        preg_match("/(\w{4})\s*(\d{4}).*?(\w{4})\s*(\d{4})/is", $notes, $matches);
                        if(isset($classes[$matches[1].$matches[2]])) {
                            $class->setComplete($classes[$matches[1].$matches[2]]);
                            unset($classes[$matches[1].$matches[2]]);
                            $this->completedHours += $class->getHours();
                        }
                    } else {
                        $number = $class->getNumber();
                        if(empty($number)) {
                            foreach($classes as $key=>$class2) {
                                if($class->getDepartment() == $class2->getDepartment()) {
                                    $class->setComplete($class2);
                                    unset($classes[$key]);
                                    $this->completedHours += $class->getHours();
                                }
                            }
                        }
                    }
                }
            }
        }

        public function display($catalogYear, $year, $semester, &$notes) {
            global $cardinalNumString;
            print '<td valign="top">';
                print '<table style="width:100%;">';
                    print '<tr class="noborder">';
                        print '<td colspan="3">';
                            print '<table width="100%" class="semesterHeader">';
                                print '<tr>';
                                    print '<td class="semesterTitle">';
                                        print $cardinalNumString[$i].' Semester - ';
                                        print ($semester % 2 == 0)?"Fall":"Spring";
                                        print " ".$year;
                                    print '</td>';
                                    print '<td class="semesterHours">';
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
    }
?>