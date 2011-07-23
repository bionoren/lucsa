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
    require_once($path."db/SQLiteManager.php");

    /**
     * @property-read ARRAY $majors
     * @property-read INTEGER $year
     * @property-read ARRAY $years
     * @property-read INTEGER $yearID
     * @property-read INTEGER $userID
     * @property-read BOOLEAN $activated
     */
    class Main extends Object {
        protected $majors;
        protected $year;
        protected $years;
        protected $yearID;
        protected $userID;
        protected $activated = false;
        protected static $singleton = null;

        protected function __construct() {
            $this->initUserID();

            $this->years = $this->getYears();
            if(!isset($_REQUEST["year"])) {
                $this->yearID = current(array_keys($this->years));
                $this->year = $this->years[$this->yearID];
            } else {
                $this->year = intval($_REQUEST["year"]);
                $this->yearID = array_search($this->year, $this->years);
            }
            //get all the degree options
            $this->majors = $this->getMajors($this->yearID);
        }

        public static function getInstance() {
            if(!Main::$singleton) {
                Main::$singleton = new Main();
            }
            return Main::$singleton;
        }

        /**
         * Gets the user's ID either from the session or from database.
         *
         * @return INTEGER The user's ID.
         */
        protected function initUserID() {
            if(!empty($_SESSION["userID"])) {
                $this->userID = $_SESSION["userID"];
                $this->activated = (bool)$_SESSION["activated"];
            } elseif(isset($_REQUEST["username"])) {
                //we need the name to be consistent
                $name = encrypt($_REQUEST["username"], md5($_REQUEST["password"]));
                $result = SQLiteManager::getInstance()->select("users", array("ID", "activated"), array("user"=>$name));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if($row != false) {
                    $this->userID = $_SESSION["userID"] = $row["ID"];
                    $this->activated = $_SESSION["activated"] = (bool)$row["activated"];
                } else {
                    SQLiteManager::getInstance()->insert("users", array("user"=>$name));
                    $this->userID = $_SESSION["userID"] = SQLiteManager::getInstance()->getLastInsertID();
                }
                unset($salt);
                unset($name);
            } elseif(isset($_COOKIE["userID"])) {
                $result = SQLiteManager::getInstance()->select("users", array("ID", "activated"), array("ID"=>$_COOKIE["userID"]));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $this->userID = $_SESSION["userID"] = $row["ID"];
                $this->activated = $_SESSION["activated"] = (bool)$row["activated"];
            } else {
                //create a new bogus user for them
                $result = SQLiteManager::getInstance()->insert("users", array("user"=>md5(time())));
                $this->userID = $_SESSION["userID"] = SQLiteManager::getInstance()->getLastInsertID();
                //set a cookie for a year
                setcookie("userID", $this->userID, time()+60*60*24*7*56);
            }
        }

        public function activateUser() {
            SQLiteManager::getInstance()->update("users", array("activated"=>true), array("ID"=>$this->userID));
            $this->activated = $_SESSION["activated"] = true;
        }

        /**
         * Gets a list of all the years we have data for.
         *
         * @return ARRAY List of valid years.
         */
        protected function getYears() {
            $result = SQLiteManager::getInstance()->query("SELECT ID,year FROM years ORDER BY year DESC");
            $years = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $years[$row["ID"]] = $row["year"];
            }
            return $years;
        }
        /**
         * Gets a list of all the majors we have data for.
         *
         * @param INTEGER $year The year to fetch data for.
         * @return ARRAY List of valid majors.
         */
        protected function getMajors($year) {
           $result = SQLiteManager::getInstance()->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='1' ORDER BY name");
           $majors = array();
           while($row = $result->fetchArray(SQLITE3_ASSOC)) {
               $majors[$row["acronym"]] = $row;
           }
           return $majors;
        }

        /**
         * Gets a list of all the minors we have data for.
         *
         * @param INTEGER $year The year to fetch data for.
         * @return ARRAY List of valid minors.
         */
        protected function getMinors($year) {
           $result = SQLiteManager::getInstance()->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='2' ORDER BY name");
           $minors = array();
           while($row = $result->fetchArray(SQLITE3_ASSOC)) {
               $minors[$row["acronym"]] = $row;
           }
           return $minors;
        }
    }
?>