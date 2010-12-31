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

    /**
     * Manages interactions with an individual class.
     *
     * @author Bion Oren
     */
    class Course {
        /** STRING Cached query used to fetch class information from the database. */
        const fetchSQL = "SELECT classes.*,
                       departments.department, departments.linkid AS deptlinkid
                       FROM classes
                       LEFT OUTER JOIN departments ON classes.departmentID = departments.ID ";

        /** INTEGER Psuedo-unique identifier for this class (psuedo because AJAX request can regenerate low numbered IDs. */
        protected static $instanceID = 1;

        /** Course A Course object which was taken to complete this course. */
        protected $completeClass = null;
        /** STRING The 4 letter department code for this class. */
        protected $department;
        /** STRING The magic code used to link back to this department in the LETU catalog. */
        protected $departmentlinkid;
        /** INTEGER The number of credit hours this course is worth*/
        protected $hours;
        /** INTEGER The primary key for this class in the database. */
        protected $ID;
        /** True if this class is being used as a substitute for another class. */
        public $isSubstitute = false;
        /** STRING The magic code linking this course back to the LETU catalog. */
        protected $linkid;
        /** INTEGER The ID of a note associated with this class. */
        protected $noteID = null;
        /** INTEGER The number for this course. */
        protected $number;
        /**
         * INTEGER Status of semesters this course is offered.
         * @see dbinit.php
         */
        protected $offered;
        /** STRING Course title. */
        protected $title;
        /**
         * INTEGER The psuedo-unique id for this class.
         * @see instanceID
         */
        protected $UID;
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
            $this->UID = Course::$instanceID++;
            $this->ID = intval($row["ID"]);
            $this->department = $row["department"];
            $this->departmentlinkid = $row["deptlinkid"];
            $this->number = $row["number"];
            $this->title = $row["title"];
            $this->linkid = $row["linkid"];
            $this->hours = $row["hours"];
            $this->offered = $row["offered"];
            $this->years = $row["years"];
        }

        /**
         * Returns true if the provided class is effectively equal to this one.
         *
         * @param Course $class Class to compare to.
         * @return BOOLEAN True if they are essentially equal.
         */
        public function equals(Course $class) {
            return $class->getID() == $this->getID() && $class->getTitle() == $this->getTitle();
        }

        /**
         * Returns the class completing this one.
         *
         * @return Course Completing class (if any).
         */
        public function getCompleteClass() {
            return $this->completeClass;
        }

        /**
         * Getter for the department.
         *
         * @return STRING Department code.
         * @see department
         */
        public function getDepartment() {
            return $this->department;
        }

        /**
         * Getter for the department linkID.
         *
         * @return STRING Link ID.
         * @see departmentLinkID
         */
        public function getDepartmentLink() {
            return $this->departmentlinkid;
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
         * Getter for the class hours.
         *
         * @return INTEGER Credit hours.
         * @see hours
         */
        public function getHours() {
            return $this->hours;
        }

        /**
         * Getter for the class ID.
         *
         * @return INTEGER ID.
         * @see ID
         */
        public function getID() {
            return $this->ID;
        }

        /**
         * Returns this classes' department and number together.
         *
         * @return STRING DepartmentNumber course identifier.
         * @see getDepartment()
         * @see getNumber()
         */
        public function getLabel() {
			return $this->getDepartment().$this->getNumber();
		}

        /**
         * Getter for the class link.
         *
         * @return STRING Catalog link.
         * @see linkid
         */
        public function getLink() {
            return $this->linkid;
        }

        /**
         * Getter for the note ID
         *
         * @return INTEGER Note ID.
         * @see noteID
         */
        public function getNoteID() {
            return $this->noteID;
        }

        /**
         * Getter for the class number.
         *
         * @return INTEGER Class number.
         * @see number
         */
        public function getNumber() {
            return $this->number;
        }

        /**
         * Getter for the class' semester offered status.
         *
         * @return INTEGER Semester offered status.
         * @see offered
         */
        public function getOffered() {
            return $this->offered;
        }

        /**
         * Getter for the class title.
         *
         * @return STRING Class title.
         * @see title
         */
        public function getTitle() {
            return $this->title;
        }

        /**
         * Return's this class' psuedo-UID
         *
         * @return STRING Psuedo-UID
         * @see UID
         */
        public function getUID() {
            return $this->getID()."~".$this->UID;
        }

        /**
         * Getter for the class' yearly offered status.
         *
         * @return INTEGER Yearly offered status.
         * @see years
         */
        public function getYears() {
            return $this->years;
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
         * Associates this class with a note.
         *
         * @param INTEGER $id Note ID.
         * @return VOID
         * @see noteID
         */
        public function setNoteID($id) {
            $this->noteID = $id;
        }

        /**
         * Returns a simple string represenation of this class.
         *
         * @return STRING Debug string.
         */
        public function __toString() {
            return $this->getDepartment().$this->getNumber()." - ".$this->getTitle();
        }
    }
?>