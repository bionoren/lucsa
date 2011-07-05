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
        protected static $userID;

        const DEGREE_SEPERATOR = ",";
        protected static $lastTab = -1;
        protected $sequences = array();
        protected $number;
        protected $id;
        protected $modified = false;
        protected $substitute;
        protected $classesTaken;

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

        public function getSubstitutes() {
            return $this->substitute;
        }

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

        public function addDegree($degree) {
            $courseSequence = CourseSequence::getFromID($degree);
            $courseSequence->setClassesTaken($this->classesTaken);
            $this->sequences[$courseSequence->getID()] = $courseSequence;
            $this->modified = true;
        }

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

        public function getDegrees() {
            return $this->sequences;
        }

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