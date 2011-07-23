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
    require_once($path."CourseSequence.php");
    require_once($path."db/SQLiteManager.php");
    require_once($path."Autocompleter.php");

    /**
     * Associates majors and minors in a single tab.
     *
     * @author Bion Oren
     * @property Array $degrees List of CourseSequence objects aggregated in this tab.
     * @property ClassList $substitutes List of classes that are available to substitute for a class.
     */
    class Tab extends Object {
        const DEGREE_SEPERATOR = ",";

        /** INTEGER The number of the last tab. */
        protected static $lastTab = -1;

        /** INTEGER Primary key of this tab in the database.*/
        protected $id;
        /** BOOLEAN True if the database needs to be updated for this tab. */
        protected $modified = false;
        /** INTEGER This tab's order in the tab bar. */
        protected $number;
        /** CourseSequence The master course sequence (aggregates $degrees). */
        protected $cs;
        /** ARRAY List of CourseSequence objects aggregated in this tab. */
        protected $degrees = array();
        /** ClassList List of classes that are available to substitute for a class. */
        protected $substitutes;

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
            $this->substitutes = new ClassList();
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
            $courseSequence->setClassesTaken($this->substitutes);
            $this->degrees[$courseSequence->ID] = $courseSequence;
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
            $autocompleter = new Autocompleter($this->substitutes, $this->cs->getClasses(), $this->cs->notes);
            $autocompleter->substitute();
        }

        /**
         * Clears out all the degrees in this tab.
         *
         * @return VOID
         */
        public function clearDegrees() {
            $this->degrees = array();
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
                    $substitutes[$class->completeClass->ID] = $class->completeClass;
                }
                $this->substitutes = $this->substitutes->filter(function($class) use ($substitutes) {
                    return !isset($substitutes[$class->ID]);
                });
            }
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            $this->substitutes[$transferClass->ID]->isSubstitute = false;
            $this->substitutes->sort();

            if($this->modified) {
                SQLiteManager::getInstance()->update("userTabs", array("degreeList"=>implode(Tab::DEGREE_SEPERATOR, array_keys($this->degrees))), array("ID"=>$this->id));
            }
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
         * Sets the list of classes the user has taken.
         * @param ClassList $classesTaken Classes taken.
         * @return VOID
         */
        public function setClassesTaken(ClassList $classesTaken) {
            $this->substitutes = $classesTaken;
            $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
            if(empty($this->substitutes[$transferClass->ID])) {
                $this->substitutes[$transferClass->ID] = $transferClass;
            }
            if($this->cs) {
                $this->cs->setClassesTaken($classesTaken);
            }
        }
    }
?>