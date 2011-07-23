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

    /**
     * Manages interactions with an individual class.
     *
     * @author Bion Oren
     * @property-read Course $completeClass A Course object which was taken to complete this course.
     * @property-read STRING $department The 4 letter department code for this class.
     * @property-read STRING $departmentlinkid The magic code used to link back to this department in the LETU catalog.
     * @property-read INTEGER $hours The number of credit hours this course is worth.
     * @property-read INTEGER $ID The primary key for this class in the database.
     * @property-read STRING $linkid The magic code linking this course back to the LETU catalog.
     * @property-read INTEGER $number The number for this course.
     * @property-read INTEGER $offered Status of semesters this course is offered.
     * @property-read STRING $title Course title.
     * @property-read INTEGER $years Status of years this course is offered.
     */
    class Course extends Object {
        /** STRING Cached query used to fetch class information from the database. */
        const fetchSQL = "SELECT classes.*,
                       departments.department, departments.linkid AS deptlinkid
                       FROM classes
                       LEFT OUTER JOIN departments ON classes.departmentID = departments.ID ";
        /** INTEGER Indicates a prerequisite dependency. */
        const PREREQ = 1;
        /** INTEGER Indicates a corequisite dependency. */
        const COREQ = 2;
        /** INTEGER Indicates a either a prerequisite or a corequisite dependency. */
        const EITHER = 3;

        /** Course A Course object which was taken to complete this course. */
        protected $completeClass = null;
        /** STRING The 4 letter department code for this class. */
        protected $department;
        /** STRING The magic code used to link back to this department in the LETU catalog. */
        protected $departmentlinkid;
        /** ARRAY List of course dependencies, keyed by the dependency constants in this class. */
        protected $dependencies;
        /** INTEGER The number of credit hours this course is worth. */
        protected $hours;
        /** INTEGER The primary key for this class in the database. */
        protected $ID;
        /** True if this class is being used as a substitute for another class. */
        public $isSubstitute = false;
        /** STRING The magic code linking this course back to the LETU catalog. */
        protected $linkid;
        /** INTEGER The ID of a note associated with this class. */
        public $noteID = null;
        /** INTEGER The number for this course. */
        protected $number;
        /**
         * INTEGER Status of semesters this course is offered.
         * @see dbinit.php
         */
        protected $offered;
        /** STRING Course title. */
        protected $title;
        /** INTEGER Psuedo-unique number assigned to this course instance. */
        protected $uid;
        /**
         * INTEGER Status of years this course is offered.
         * @see dbinit.php
         */
        protected $years;

        /**
         * Conctructs a new course from database information.
         *
         * @param ARRAY $row Associative array of database info.
         * @see fetchSQL
         */
        protected function __construct(array $row) {
            $this->uid = mt_rand();
            $this->ID = intval($row["ID"]);
            $this->department = $row["department"];
            $this->departmentlinkid = $row["deptlinkid"];
            $this->number = $row["number"];
            $this->title = $row["title"];
            $this->linkid = $row["linkid"];
            $this->hours = $row["hours"];
            $this->offered = $row["offered"];
            $this->years = $row["years"];
            $this->setupDependencies();
        }

        /**
         * Returns true if the provided class is effectively equal to this one.
         *
         * @param Course $class Class to compare to.
         * @return BOOLEAN True if they are essentially equal.
         */
        public function equals(Course $class) {
            return $class->ID == $this->ID && $class->title == $this->title;
        }

        /**
         * Returns a list of this class' corequisites.
         *
         * @return MIXED Dependency string or something falsy if their are no corequisites.
         * @warning The return value in the no coreq case is only guaranteed to evaluate to false, not to be some particular false value.
         */
        public function getCorequisites() {
            return $this->dependencies[Course::COREQ];
        }

        /**
         * Returns a new Course object generated from a database row.
         *
         * @param INTEGER $id The primary key of the class.
         * @return Course Class corresponding to the provided key.
         */
        public static function getFromID($id) {
            $db = SQLiteManager::getInstance();
            $sql = Course::fetchSQL."WHERE classes.ID=".$id;
            $result = $db->query($sql);
            return new Course($result->fetchArray(SQLITE3_ASSOC));
        }

        /**
         * Returns a new Course object that has the provided department and number.
         *
         * @param STRING $dept Department code.
         * @param INTEGER $num Course number.
         * @param STRING $title Optional title to provide for the new class.
         * @return Course New Course object with the given department and number (and optionally title), or null if no such class exists.
         */
        public static function getFromDepartmentNumber($dept, $num, $title="") {
            $db = SQLiteManager::getInstance();
            //try to get the class from our year if we can
            $sql = Course::fetchSQL."WHERE departments.department='".$dept."' AND ".$num." BETWEEN classes.number AND classes.endNumber";
            $result = $db->query($sql);
            $ret = $result->fetchArray(SQLITE3_ASSOC);
            if($ret === false) {
                return null;
            }
            if(!empty($title)) {
                $ret["title"] = $title;
                $ret["number"] = $num;
            }
            return new Course($ret);
        }

        /**
         * Returns this classes' department and number together.
         *
         * @return STRING DepartmentNumber course identifier.
         * @see getDepartment()
         * @see getNumber()
         */
        public function getLabel() {
			return $this->department.$this->number;
		}

        /**
         * Returns a list of this class' dependencies that can be either prerequisites or corequisites.
         *
         * @return MIXED Dependency string or something falsy if their are no applicable dependencies.
         * @warning The return value in the no dependencies case is only guaranteed to evaluate to false, not to be some particular false value.
         */
        public function getPreOrCorequisites() {
            return $this->dependencies[Course::EITHER];
        }

        /**
         * Returns a list of this class' prerequisites.
         *
         * @return MIXED Dependency string or something falsy if their are no prerequisites.
         * @warning The return value in the no prereq case is only guaranteed to evaluate to false, not to be some particular false value.
         */
        public function getPrerequisites() {
            return $this->dependencies[Course::PREREQ];
        }

        /**
         * Returns a unique ID for this class instance.
         *
         * @return STRING UID.
         * @see uid
         */
        public function getUID() {
            return $this->ID."_".$this->uid;
        }

        /**
         * Returns true if this class has been completed.
         *
         * @return BOOLEAN True if the class has been completed.
         */
        public function isComplete() {
            return $this->completeClass !== null;
        }

        /**
         * Completes this class.
         *
         * @param Course $class Class completing this one.
         * @return VOID
         */
        public function setComplete(Course $class) {
            $this->completeClass = $class;
            $class->isSubstitute = true;
        }

        /**
         * Sets up course dependencies for this class.
         *
         * @return VOID
         */
        protected function setupDependencies() {
            $this->dependencies = array();
            $result = SQLiteManager::getInstance()->query("SELECT type, data FROM classDependencyMap WHERE classID = '".$this->ID."'");
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $this->dependencies[$row["type"]] = $row["data"];
            }
        }

        /**
         * Returns a simple string represenation of this class.
         *
         * @return STRING Debug string.
         */
        public function __toString() {
            return $this->department.$this->number." - ".$this->title;
        }
    }
?>