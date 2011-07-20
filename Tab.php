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

        /** INTEGER Primary key of this tab in the database.*/
        protected $id;
        /** BOOLEAN True if the database needs to be updated for this tab. */
        protected $modified = false;
        /** INTEGER This tab's order in the tab bar. */
        protected $number;
        /** CourseSequence The master course sequence (aggregates $sequences). */
        protected $cs;
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
            $courseSequence->setClassesTaken($this->substitute);
            $this->sequences[$courseSequence->getID()] = $courseSequence;
            $this->modified = true;
            $this->cs = $courseSequence;
        }

        /**
         * Automatically applies course substitutions using Autocompleter.
         * @return VOID
         * @see Autocompleter
         */
        protected function autocomplete() {
            $this->cs->applySubstitions();
            $autocompleter = new Autocompleter($this->substitute, $this->cs->getClasses(), $this->cs->getNotes());
            $autocompleter->substitute();
        }

        /**
         * Clears out all the degrees in this tab.
         *
         * @return VOID
         */
        public function clearDegrees() {
            $this->sequences = array();
            $this->modified = true;
            $this->cs = null;
        }

        /**
         * Performs miscellaneous operations to prepare the tab for display.
         *
         * @param BOOLEAN $autocomplete True if class autocompletion should be run.
         * @return VOID
         */
        public function finalize($autocomplete=false) {
            if($this->cs) {
                if($autocomplete) {
                    $this->autocomplete();
                }
                $this->cs->applySubstitions();

                $temp = $this->cs->getClasses()->filter(function(Course $class) { return $class->isComplete(); });
                $substitutes = new ClassList();
                foreach($temp as $class) {
                    $substitutes[$class->getCompleteClass()->getID()] = $class->getCompleteClass();
                }
                $this->substitute = $this->substitute->filter(function($class) use ($substitutes) {
                    return !isset($substitutes[$class->getID()]);
                });
            }
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            $this->substitute[$transferClass->getID()]->isSubstitute = false;
            $this->substitute->sort();

            if($this->modified) {
                SQLiteManager::getInstance()->update("userTabs", array("degreeList"=>implode(Tab::DEGREE_SEPERATOR, array_keys($this->sequences))), array("ID"=>$this->id));
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
         * @return ARRAY List of tabs for the user.
         */
        public static function getFromDB() {
            $result = SQLiteManager::getInstance()->query("SELECT ID, number, degreeList FROM userTabs WHERE userID=".Main::getInstance()->userID." ORDER BY number");
            $ret = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if($row["degreeList"]) {
                    $degrees = explode(Tab::DEGREE_SEPERATOR, $row["degreeList"]);
                } else {
                    $degrees = array();
                }
                $ret[] = new Tab($row["ID"], $row["number"], $degrees);
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
                SQLiteManager::getInstance()->insert("userTabs", array("userID"=>Main::getInstance()->userID, "number"=>$number));
                $id = SQLiteManager::getInstance()->getLastInsertID();
            } else {
                $result = SQLiteManager::getInstance()->select("userTabs", array("number", "degreeList"), array("ID"=>$id));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $number = $row["number"];
                $degrees = explode(Tab::DEGREE_SEPERATOR, $row["degreeList"]);
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
            $this->substitute = $classesTaken;
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            if(empty($this->classesTaken[$transferClass->getID()])) {
                $this->substitute[$transferClass->getID()] = $transferClass;
            }
            if($this->cs) {
                $this->cs->setClassesTaken($classesTaken);
            }
        }
    }
?>