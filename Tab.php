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

    require_once($path."CourseSequence.php");
    require_once($path."db/SQLiteManager.php");
    require_once($path."Autocompleter.php");

    /**
     * Associates majors and minors in a single tab.
     *
     * @author Bion Oren
     */
    class Tab {
        const DEGREE_SEPERATOR = ",";

        /** INTEGER The number of the last tab. */
        protected static $lastTab = -1;
        /** INTEGER ID of the current user. */
        protected static $userID;

        /** ClassList List of classes that have been taken by the user. */
        protected $classesTaken;
        /** INTEGER Primary key of this tab in the database.*/
        protected $id;
        /** BOOLEAN True if the database needs to be updated for this tab. */
        protected $modified = false;
        /** INTEGER This tab's order in the tab bar. */
        protected $number;
        /** ARRAY List of CourseSequence objects aggregated in this tab. */
        protected $sequences = array();
        /** ClassList List of classes that are available to substitute for a class. */
        protected $substitute;

        /**
         * Constructs a new tab.
         *
         * @param INTEGER $id Primary key for this tab.
         * @param INTEGER $number This tab's order in the tab bar.
         * @param ARRAY $degrees List of CourseSequence IDs in this tab.
         */
        protected function __construct($id, $number, array $degrees) {
            if($number > $this::$lastTab) {
                $this::$lastTab = $number;
            }
            $this->id = $id;
            $this->number = $number;
            $this->substitute = new ClassList();
            $this->classesTaken = new ClassList();
            foreach($degrees as $degree) {
                $this->addDegree($degree);
            }
            $this->modified = false;
        }

        /**
         * Adds a degree to this tab.
         *
         * @param INTEGER $degree ID of the degree to add.
         * @return VOID
         */
        public function addDegree($degree) {
            $courseSequence = CourseSequence::getFromID($degree);
            $courseSequence->setClassesTaken($this->classesTaken);
            $this->sequences[$courseSequence->getID()] = $courseSequence;
            $this->modified = true;
        }

        /**
         * Automatically applies course substitutions using Autocompleter.
         * @return VOID
         * @see Autocompleter
         */
        protected function autocomplete() {
            foreach($this->sequences as $cs) {
                $autocompleter = new Autocompleter($this->substitute, $cs->getClasses(), $cs->getNotes());
                $autocompleter->substitute($this::$userID);
            }
            foreach($this->sequences as $cs) {
                $cs->applySubstitions($this::$userID);
            }
            $this->substitute = $this->substitute->filter(function(Course $class) { return !$class->isSubstitute; });
        }

        /**
         * Performs miscellaneous operations to prepare the tab for display.
         *
         * @param BOOLEAN $autocomplete True if class autocompletion should be run.
         * @return VOID
         */
        public function finalize($autocomplete=false) {
            foreach($this->sequences as $sequence) {
                $sequence->applySubstitions($this::$userID);
            }
            $this->substitute = $this->classesTaken->filter(function(Course $class) { return !$class->isSubstitute; });

            if($autocomplete) {
                $this->autocomplete();
            }
            $this->substitute->sort();
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            $this->classesTaken[$transferClass->getID()]->isSubstitute = false;

            if($this->modified) {
                $db = SQLiteManager::getInstance();
                $db->update("userTabs", array("degreeList"=>implode(DEGREE_SEPERATOR, array_keys($this->sequences))), array("ID"=>$this->id));
            }
        }

        /**
         * Returns the list of CourseSequences in this tab.
         *
         * @return Array List of CourseSequence objects.
         */
        public function getDegrees() {
            return $this->sequences;
        }

        /**
         * Gets all tabs for user.
         *
         * @param INTEGER $userID User ID.
         * @return ARRAY List of tabs for the user.
         */
        public static function getFromDB($userID) {
            Tab::$userID = $userID;

            $db = SQLiteManager::getInstance();
            $result = $db->query("SELECT ID, number, degreeList FROM userTabs WHERE userID=$userID ORDER BY number");
            $ret = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ret[] = new Tab($row["ID"], $row["number"], explode(DEGREE_SEPERATOR, $row["degreeList"]));
            }
            return $ret;
        }

        /**
         * Creates a tab from the given tab ID, or creates a new tab if no ID is specified.
         *
         * @param INTEGER $id ID of the tab to fetch, or -1 to create a new tab.
         * @return Tab New tab object.
         */
        public static function getFromID($id=-1) {
            if($id == -1) {
                $number = ++Tab::$lastTab;
                $degrees = array();
                //create tab in database
                $db = SQLiteManager::getInstance();
                $db->insert("userTabs", array("userID"=>Tab::$userID, "number"=>$number));
                $id = $db->getLastInsertID();
            } else {
                $result = $db->select("userTabs", array("number", "degreeList"), array("ID"=>$id));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $number = $row["number"];
                $degrees = explode(DEGREE_SEPERATOR, $row["degreeList"]);
            }
            return new Tab($id, $number, $degrees);
        }

        /**
         * Returns the list of classes available as substitutes.
         *
         * @return ClassList List of substitutable classes.
         * @see substitute
         */
        public function getSubstitutes() {
            return $this->substitute;
        }

        /**
         * Sets the list of classes the user has taken.
         * @param ClassList $classesTaken Classes taken.
         * @return VOID
         */
        public function setClassesTaken(ClassList $classesTaken) {
            $this->classesTaken = clone $classesTaken;
            foreach($this->sequences as $cs) {
                $cs->setClassesTaken($this->classesTaken);
            }
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            if(empty($this->classesTaken[$transferClass->getID()])) {
                $this->classesTaken[$transferClass->getID()] = $transferClass;
            }
        }
    }
?>