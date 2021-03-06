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

    require_once($path."Object.php");
    require_once($path."ClassList.php");
    require_once($path."Course.php");

    /**
     * Manages interactions with a semester.
     *
     * @author Bion Oren
     * @property-read ClassList $classes List of classes in this semester.
     * @property-read INTEGER $completedHours Number of completed hours.
     * @property-read INTEGER $hours Total number of hours.
     * @property-read INTEGER $order The numerical order of this semester in a course sequence.
     */
    class Semester extends Object {
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

        /** ClassList List of classes in this semester. */
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
        public $semesterID;
        /** INTEGER The year this semester is supposed to occur in. */
        public $year;

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
            $this->classes[$class->ID] = $class;
            $this->hours += $class->hours;
            if($class->isComplete()) {
                $this->completedHours += $class->hours;
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
            $this->completedHours += $class->hours;
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
                if(!$class->isComplete() && isset($mapping[$old])) {
                    $new = $mapping[$old];
                    $this->completeClass($class, $classes[$new]);
                    unset($mapping[$old]);
                    if(!($classes[$new]->department == "LETU" && $classes[$new]->number == "4999")) {
                        unset($classes[$new]);
                    }
                }
            }
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
                    $class->noteID = $notes->add($row["notes"]);
                }
                $classes[] = $class;
            }
            return new Semester($classes, $semester);
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
                    $ret[$class->ID] = $class;
                }
            }
            return $ret;
        }

        /**
         * Returns a unique ID for this semester instance.
         *
         * @return STRING UID.
         */
        public function getUID() {
            return $this->order;
        }

        /**
         * Checks to see if a class is in this semester.
         *
         * @param Course $class Class to search for.
         * @return BOOLEAN True if the class is in this semester.
         */
        public function hasClass(Course $class) {
            return isset($this->classes[$class->ID]);
        }

        /**
         * Removes all classes matching $id from this semester.
         *
         * @param INTEGER $id Course id to remove.
         * @return VOID
         */
        public function removeClass($id) {
            $class = $this->classes[$id];
            $this->hours -= $class->hours;
            if($class->isComplete()) {
                $this->completedHours -= $class->hours;
            }
            unset($this->classes[$id]);
        }
    }
?>