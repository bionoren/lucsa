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
        /** ARRAY 12 numerical strings to identify semesters. */
        public static $CARDINAL_STRINGS = array("First", "Second", "Third", "Fourth", "Fifth", "Sixth", "Seventh", "Eighth", "Ninth", "Tenth", "Eleventh", "Twelth");

        /** ARRAY List of semester names. */
        public static $SEMESTERS = array("Spring", "Summer", "Fall");
        /** INTEGER Index into $SEMESTERS for spring. */
        const SPRING = 0;
        /** INTEGER Index into $SEMESTERS for summer. */
        const SUMMER = 1;
        /** INTEGER Index into $SEMESTERS for fall. */
        const FALL = 2;

        /** ARRAY List of classes in this semester. */
        protected $classes;
        /** INTEGER Number of completed hours. */
        protected $completedHours = 0;
        /** INTEGER Total number of hours. */
        protected $hours = 0;
        /** INTEGER The numerical order of this semester in a course sequence. */
        protected $order;
        /**
         * INTEGER The type of semester this is.
         * @see $SEMESTERS
         */
        protected $semesterID;
        /** INTEGER The year this semester is supposed to occur in. */
        protected $year;

        /**
         * Constructs a new semester from a list of classes.
         *
         * @param ARRAY $classes List of Course objects in this semester.
         * @param INTEGER $order The position of this semester in a course sequence.
         */
        protected function __construct(array $classes=array(), $order) {
            $this->classes = new ClassList();
            array_walk($classes, array($this, "addClass"));
            $this->order = $order;
        }

        /**
         * Adds a Course to this semester.
         *
         * @param Course $class Class to add.
         * @return VOID
         */
        public function addClass(Course $class) {
            $this->classes[$class->getID()] = $class;
            $this->hours += $class->getHours();
            if($class->isComplete()) {
                $this->completedHours += $class->getHours();
            }
        }

        /**
         * Completes a class.
         *
         * @param Course $class The class to be completed.
         * @param Course $completingClass The class that was taken.
         * @return VOID
         */
        public function completeClass(Course $class, Course $completingClass) {
            $class->setComplete($completingClass);
            $this->completedHours += $class->getHours();
        }

        /**
         * Maps classes that have been taken to the classes they completed.
         *
         * @param ClassList $classes Classes that have been taken.
         * @param ClassList $mapping Mapping between classes in a course sequence and classes that have been taken.
         * @return VOID
         */
        public function evalTaken(ClassList $classes, ClassList $mapping) {
            foreach($this->classes as $old=>$class) {
                if(isset($mapping[$old])) {
                    $new = $mapping[$old];
                    $this->completeClass($class, $classes[$new]);
                    unset($mapping[$old]);
                    if(!($classes[$new]->getDepartment() == "LETU" && $classes[$new]->getNumber() == "4999")) {
                        unset($classes[$new]);
                    }
                }
            }
        }

        /**
         * Used by autosubstitution to infer associations.
         *
         * This function assumes that all classes are available to be completed and all
         * provided classes are available to complete other classes.
         * THIS IS NOT TESTED!
         *
         * @param ClassList $classes List of classes that have been taken.
         * @param INTEGER $user ID of the user who has taken classes.
         * @param Notes $notes Optional Notes object.
         * @return VOID
         */
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

        /**
         * Getter for the class array.
         *
         * @return ARRAY List of classes.
         * @see classes
         */
        public function getClasses() {
            return $this->classes;
        }

        /**
         * Getter for the number of completed hours.
         *
         * @return INTEGER Number of completed hours.
         * @see completedHours
         */
        public function getCompletedHours() {
            return $this->completedHours;
        }

        /**
         * Creates a new semester within the context of a degree.
         *
         * @param INTEGER $degreeID Primary key for the degree the semester will be in.
         * @param INTEGER $semester The order of this semester in the degree.
         * @param Notes $notes A place to fetch and store class notes to/from.
         * @return Semester A new semester object.
         */
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

        /**
         * Getter for the number of hours.
         *
         * @return INTEGER Number of hours.
         * @see hours
         */
        public function getHours() {
            return $this->hours;
        }

        /**
         * Getter for the type of this semester
         *
         * @return INTEGER Semester type
         * @see semesterID
         */
        public function getID() {
            return $this->semesterID;
        }

        /**
         * Returns a list of all the incomplete classes in this semester.
         *
         * @return ClassList List of incomplete classes.
         */
        public function getIncompleteClasses() {
            $ret = new ClassList();
            foreach($this->classes as $class) {
                if(!$class->isComplete()) {
                    $ret[$class->getID()] = $class;
                }
            }
            return $ret;
        }

        /**
         * Getter for the year for this semester
         *
         * @return INTEGER Semester year
         * @see year
         */
        public function getYear() {
            return $this->year;
        }

        /**
         * Checks to see if a class is in this semester.
         *
         * @param Course $class Class to search for.
         * @return BOOLEAN True if the class is in this semester.
         */
        public function hasClass(Course $class) {
            return isset($this->classes[$class->getID()]);
        }

        /**
         * Removes all classes matching $id from this semester.
         *
         * @param INTEGER $id Course id to remove.
         * @return VOID
         */
        public function removeClass($id) {
            $class = $this->classes[$id];
            $this->hours -= $class->getHours();
            if($class->isComplete()) {
                $this->completedHours -= $class->getHours();
            }
            unset($this->classes[$id]);
        }

        /**
         * Setter for the semester type.
         *
         * @param INTEGER $semesterID Semester type.
         * @return VOID
         * @see semesterID
         */
        public function setSemester($semesterID) {
            $this->semesterID = $semesterID;
        }

        /**
         * Setter for the year.
         *
         * @param INTEGER $year Year.
         * @return VOID
         * @see year
         */
        public function setYear($year) {
            $this->year = $year;
        }
    }
?>