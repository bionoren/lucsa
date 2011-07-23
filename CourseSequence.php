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
    require_once($path."Semester.php");

    /**
     * Manages interaction with a major or minor's course sequence.
     *
     * @author Bion Oren
     * @property-read STRING $acronym The short acronym associated with this sequence.
     * @property-read INTEGER $completedHours Number of hours completed in this sequence.
     * @property-read INTEGER $hours Number of hours in this sequence.
     * @property-read INTEGER $ID The primary key for this sequence in the database.
     * @property-read STRING $linkID The magic link ID for this sequence back to the LETU catalog.
     * @property-read STRING $name The full name for this sequence.
     * @property-read Notes $notes Container for the notes associated with classes in this sequence.
     * @property-read ARRAY $semesters List of semesters in this course sequence.
     */
    class CourseSequence extends Object {
        /** STRING The short acronym associated with this sequence. */
        protected $acronym;
        /** INTEGER Number of hours completed in this sequence. */
        protected $completedHours;
        /** INTEGER Number of hours in this sequence. */
        protected $hours;
        /** INTEGER The primary key for this sequence in the database. */
        protected $ID;
        /** STRING The magic link ID for this sequence back to the LETU catalog. */
        protected $linkID;
        /** STRING The full name for this sequence. */
        protected $name;
        /** Notes Container for the notes associated with classes in this sequence. */
        protected $notes;
        /** ARRAY List of semesters in this course sequence. */
        protected $semesters;
        /**
         * INTEGER The type of this sequence (major, minor, etc).
         * @see dbinit.php
         */
        protected $type;
        /** INTEGER The year this course sequence is supposed to start. */
        protected $year;
        /** ClassList List of classes that have been taken by the user. */
        protected $classesTaken = null;

        /**
         * Conctructs a new course sequence from database information.
         *
         * @param ARRAY $row Associative array of database info.
         */
        protected function __construct(array $row) {
            $this->ID = $row["ID"];
            $this->year = $row["year"];
            $this->linkID = $row["linkid"];
            $this->name = $row["name"];
            $this->acronym = $row["acronym"];
            $this->type = $row["type"]; //none, major, minor
            $this->notes = new Notes();
            $year = $this->year;
            $semNum = Semester::FALL;
            $extra = 5-($row["numSemesters"]%3);
            for($i = 1; $i <= $row["numSemesters"]+$extra; $i++) {
                $tmp = Semester::getFromDegree($this->ID, $i, $this->notes);
                $tmp->year = $year;
                $tmp->semesterID = $semNum++%count(Semester::$SEMESTERS);
                if($semNum%count(Semester::$SEMESTERS) == Semester::SPRING) {
                    $year++;
                }
                $this->hours += $tmp->hours;
                $this->semesters[] = $tmp;
            }
        }

        /**
         * Applies course substitutions in this major for a given user.
         *
         * @param INTEGER $user The ID of user who has taken said classes.
         * @return VOID
         */
		public function applySubstitions() {
			$mapping = CourseSequence::getUserClassMap();
			$mapping->sort();
            $this->completedHours = 0;
            foreach($this->semesters as $semester) {
                $semester->evalTaken($this->classesTaken, $mapping);
                $this->completedHours += $semester->completedHours;
            }
        }

        /**
         * Returns an array mapping IDs from classes in a course sequence to classes that have been taken to complete them.
         *
         * @return ClassList Mapping of IDs from classes that should be taken to classes that have been taken.
         */
		protected static function getUserClassMap() {
            //evaluate user-defined substitutions and substitutions from notes
            //need to translate classTaken department and number keys into a DB class key
            $ret = new ClassList();
            $result = SQLiteManager::getInstance()->query("SELECT oldClassID, newClassID FROM userClassMap WHERE userID=".Main::getInstance()->userID);
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ret[$row["oldClassID"]] = $row["newClassID"];
            }
			return $ret;
		}

        /**
         * Creates a new course sequence from a specific database row.
         *
         * @param INTEGER $id Primary key of the course sequence in the database.
         * @return CourseSequence New course sequence from the db row.
         */
        public static function getFromID($id) {
            $sql = "SELECT degrees.*, years.year
                    FROM degrees
                    JOIN years ON degrees.yearID=years.ID
                    WHERE degrees.ID=".$id;
            $result = SQLiteManager::getInstance()->query($sql);
            return new CourseSequence($result->fetchArray(SQLITE3_ASSOC));
        }

        /**
         * Getter for all the classes in this course sequence.
         *
         * @return ClassList List of classes.
         */
        public function getClasses() {
            $ret = new ClassList();
            foreach($this->semesters as $sem) {
                foreach($sem->classes as $class) {
                    $ret[$class->ID] = $class;
                }
            }
            return $ret;
        }

        /**
         * Returns a list of classes that have not been completed yet.
         *
         * @return ClassList List of incomplete classes.
         */
        public function getIncompleteClasses() {
            $ret = new ClassList();
            foreach($this->semesters as $sem) {
                $ret = ClassList::merge($ret, $sem->getIncompleteClasses());
            }
            return $ret;
        }

        public function setClassesTaken(ClassList $classesTaken) {
            $this->classesTaken = clone $classesTaken;
        }

        /**
         * Returns a simple string represenation of this class.
         *
         * @return STRING Debug string.
         */
        public function __toString() {
            return $this->name;
        }
    }

    /**
     * Stores a list of unique notes.
     *
     * @author Bion Oren
     * @property-read ARRAY $notes List of note strings.
     */
    class Notes extends Object implements Countable {
        /** ARRAY List of note strings. */
        protected $notes = array();

        /**
         * Adds a note to the list, maintaining uniqueness.
         *
         * @param STRING $note Note to add.
         * @return INTEGER The index for the note.
         */
        function add($note) {
            $key = array_search($note, $this->notes);
            if($key === false) {
                $key = count($this->notes)+1;
                $this->notes[$key] = $note;
            }
            return $key;
        }

        /**
         * Returns the number of stored notes.
         *
         * @return INTEGER Number of notes.
         */
        function count() {
            return count($this->notes);
        }

        /**
         * Gets a specified note.
         *
         * @param INTEGER $id Key for the note.
         * @return STRING Requested note.
         */
        function getNote($id) {
            return $this->notes[$id];
        }

        /**
         * Returns a simple string represenation of this class.
         *
         * @return STRING Debug string.
         */
        function __toString() {
            return "Notes";
        }
    }
?>