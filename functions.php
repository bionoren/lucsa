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

	//-----------------------------
	//	   DEBUGGING FUNCTIONS
	//-----------------------------

	/**
     * Useful debug function that displays variables or arrays in a pretty format.
     *
     * @param STRING $name Name of the array (for pretty display purposes).
     * @param MIXED $array Array of data, but if it isn't an array we try to print it by itself.
     * @param STRING $member Calls a function on $array when outputing $array (assumes $array is an object or array of objects).
     * @return VOID
     */
	function dump($name, $array, $member=null) {
		if(is_array($array) || (is_object($array) && $array instanceof Iterator)) {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".htmlentities($val)."<br/>\n";
                    } else {
                        print $name."[".$key."] = ".htmlentities($val->{$member}())."<br/>\n";
                    }
                }
			}
		} else {
            if($member == null) {
    			print "$name = ".htmlentities($array)."<br/>\n";
            } else {
                print "$name = ".htmlentities($array->{$member}())."<br/>\n";
            }
        }
	}

    //-----------------------------
	//			FUNCTIONS
	//-----------------------------

	/**
	 * Fetches a file and caches it for future requests.
	 *
	 * @param STRING $file Full path to the file to fetch.
	 * @param BOOLEAN $cache False if the cache should be bypassed
	 * @return STRING File contents.
	 */
    function getCache($file, $cache=true) {
        $name = "cache/".md5($file).".tmp";
        if($cache && file_exists($name)) {
            return file_get_contents($name);
        } else {
            $ret = file_get_contents($file);
            file_put_contents($name, $ret);
            return $ret;
        }
    }

	/**
	 * Returns a private key for use in encryption.
	 *
	 * @return STRING Private key.
	 */
    function getKeyStr() {
        return "Curse:DuckInADungeon. You should know better than to pick up a duck in a dungeon.";
    }

	/**
	 * Gets a list of all the majors we have data for.
	 *
	 * @param INTEGER $year The year to fetch data for.
	 * @return ARRAY List of valid majors.
	 */
    function getMajors($year) {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='1' ORDER BY name");
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
    function getMinors($year) {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID, name, acronym FROM degrees WHERE yearID='".$year."' AND type='2' ORDER BY name");
        $minors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $minors[$row["acronym"]] = $row;
        }
        return $minors;
    }

    /**
     * Gets the user's ID either from the session or from database.
     *
     * @param SQLiteManager $db Database connection object.
     * @return INTEGER The user's ID.
     */
    function getUserID(SQLiteManager $db) {
        if(!empty($_SESSION["userID"])) {
            $userID = $_SESSION["userID"];
        } else {
            if(isset($_SERVER['PHP_AUTH_USER'])) {
                //we need the name to be consistent
                $name = encrypt($_SERVER["PHP_AUTH_USER"], md5($_SERVER["PHP_AUTH_USER"]));
                $result = $db->query("SELECT ID FROM users WHERE user='".$name."'");
                $row = $result->fetchArray(SQLITE3_ASSOC);
                if($row != false) {
                    $userID = $_SESSION["userID"] = $row["ID"];
                } else {
                    $db->insert("users", array("user"=>$name, "salt"=>$salt));
                    $userID = $_SESSION["userID"] = $db->getLastInsertID();
                }
                unset($salt);
                unset($name);
            }
        }

        return $userID;
    }

	/**
	 * Gets a list of all the years we have data for.
	 *
	 * @return ARRAY List of valid years.
	 */
    function getYears() {
        $db = SQLiteManager::getInstance();
        $result = $db->query("SELECT ID,year FROM years ORDER BY year DESC");
        $years = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $years[$row["ID"]] = $row["year"];
        }
        return $years;
    }

	/**
	 * Translates between the CXWeb version of your major's title and the course catalog's version.
	 *
	 * @param ARRAY $majors List of majors in the catalog.
	 * @param STRING $major What CXWeb thinks your major is.
	 * @return STRING $major The key for your major, but from the catalog.
	 */
    function guessMajor(array $majors, $major) {
        $min = 1000;
        $ret = "";
        foreach($majors as $key=>$mjr) {
            $try = levenshtein($major, $mjr["name"], 1, 6, 6);
            if($try < $min) {
                $min = $try;
                $ret = $key;
            }
        }
        return $ret;
    }

    /**
     * Substitutes one class for another in the database.
     *
     * Deletes any previous substitutions on this class. If $subID is null, simply removes
     * any existing substitutions on the old class.
     *
     * @param INTEGER $userID The ID of the user.
     * @param INTEGER $origID The ID of the original class.
     * @param INTEGER $subID The ID of the class to substitute (if any).
     * @return VOID
     */
    function substituteClass($userID, $origID, $subID=null) {
        if(!empty($subID)) {
            $fields["userID"] = $userID;
            $fields["oldClassID"] = $origID;
            $fields["newClassID"] = $subID;
            SQLiteManager::getInstance()->insert("userClassMap", $fields);
        } else {
            SQLiteManager::getInstance()->query("DELETE FROM userClassMap WHERE userID=$userID AND oldClassID=$origID");
        }
    }

    /**
     * Moves a class from one semester to another.
     *
     * Note that if the class ID appears multiple times in the old semester, an arbitrary
     * instance of the class will be moved.
     *
     * @param INTEGER $degree The primary key for the degree the class is in.
     * @param INTEGER $oldSem The primary key for the semester the class is in.
     * @param INTEGER $newSem The primary key for the semester the class should be in.
     * @param INTEGER $class The primary key for the class to move.
     * @return VOID
     */
    function moveClass($degree, $oldSem, $newSem, $class) {
        $where = "degreeID=$degree AND semester=$oldSem AND courseID=$class";
        $select = "SELECT ID FROM degreeCourseMap WHERE $where LIMIT 1";
        $sql = "UPDATE degreeCourseMap SET semester=$newSem WHERE $where AND ID IN ($select)";
        SQLiteManager::getInstance()->query($sql);
    }
?>