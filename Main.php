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

    require_once($path."db/SQLiteManager.php");

    class Main {
        protected $majors;
        protected $year;
        protected $years;
        protected $yearID;
        protected $userID;
        protected static $singleton = null;

        protected function __construct() {
            $this->userID = $this->getUserID();

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

        public function __get($name) {
            if(property_exists($this, $name)) {
                return $this->$name;
            }

            $trace = debug_backtrace();
            trigger_error('Undefined property via __get(): '.$name.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
            debug_print_backtrace();
            return null;
        }

        /**
         * Gets the user's ID either from the session or from database.
         *
         * @return INTEGER The user's ID.
         */
        protected function getUserID() {
            if(!empty($_SESSION["userID"])) {
                $userID = $_SESSION["userID"];
            } elseif(isset($_SERVER['PHP_AUTH_USER'])) {
                //we need the name to be consistent
                $name = encrypt($_SERVER["PHP_AUTH_USER"], md5($_SERVER["PHP_AUTH_USER"]));
                $result = SQLiteManager::getInstance()->select("users", array("ID"), array("user"=>$name));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if($row != false) {
                    $userID = $_SESSION["userID"] = $row["ID"];
                } else {
                    SQLiteManager::getInstance()->insert("users", array("user"=>$name));
                    $userID = $_SESSION["userID"] = SQLiteManager::getInstance()->getLastInsertID();
                }
                unset($salt);
                unset($name);
            } elseif(isset($_COOKIE["userID"])) {
                SQLiteManager::getInstance()->select("users", array("ID"), array("ID"=>$_COOKIE["userID"]));
                $row = $result->fetchArray(SQLITE3_ASSOC);
                $userID = $_SESSION["userID"] = $row["ID"];
            } else {
                //create a new bogus user for them
                SQLiteManager::getInstance()->insert("users", array("user"=>md5(time())));
                $userID = $_SESSION["userID"] = SQLiteManager::getInstance()->getLastInsertID();
                //set a cookie for a year
                setcookie("userID", $userID, time()+60*60*24*7*56);
            }

            return $userID;
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