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
        global $path;
        $name = $path."cache/".md5($file).".tmp";
        if($cache && file_exists($name)) {
            return file_get_contents($name);
        } else {
            //suppress any warnings
            $ret = @file_get_contents($file);
            file_put_contents($name, $ret);
            return $ret;
        }
    }

	/**
	 * Translates between the CXWeb version of your major's title and the course catalog's version.
	 *
	 * @param STRING $major What CXWeb thinks your major is.
	 * @return STRING $major The key for your major, but from the catalog.
	 */
    function guessMajor($major) {
        $min = 1000;
        $ret = "";
        foreach(Main::getInstance()->majors as $key=>$mjr) {
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
     * @param INTEGER $origID The ID of the original class.
     * @param INTEGER $subID The ID of the class to substitute (if any).
     * @return VOID
     */
    function substituteClass($origID, $subID=null) {
        if(!empty($subID)) {
            $fields["userID"] = Main::getInstance()->userID;
            $fields["oldClassID"] = $origID;
            $fields["newClassID"] = $subID;
            SQLiteManager::getInstance()->insert("userClassMap", $fields);
        } else {
            SQLiteManager::getInstance()->query("DELETE FROM userClassMap WHERE userID=".Main::getInstance()->userID." AND oldClassID=$origID");
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

    /**
     * Called when moving a class between semesters to see if we need to add more semesters or if we have too many extra semesters.
     *
     * @param INTEGER $degree ID of the degree we're moving between.
     * @param INTEGER $oldSem Semester number for the semester this class came from.
     * @param INTEGER $newSem Semester number for the semester this class is moving to.
     * @return VOID
     */
    function updateDegreeSemesters($degree, $oldSem, $newSem) {
        $numSemesters = SQLiteManager::getInstance()->select("degrees", array("numSemesters"), array("ID"=>$degree))->fetchArray(SQLITE3_NUM);
        $numSemesters = $numSemesters[0];
        //check to see if we need more semesters
        if($newSem > $numSemesters) {
            SQLiteManager::getInstance()->update("degrees", array("numSemesters"=>$newSem), array("ID"=>$degree));
        } elseif($oldSem == $numSemesters) {
            //check to see if we need less semesters
            $result = SQLiteManager::getInstance()->select("degreeCourseMap", array("ID"), array("degreeID"=>$degree, "semester"=>$oldSem));
            if(!$result->fetchArray(SQLITE3_NUM)) {
                $lastSemesterSQL = "SELECT semester FROM degreeCourseMap WHERE degreeID=$degree ORDER BY semester DESC LIMIT 1";
                $sql = "UPDATE degrees SET numSemesters = ($lastSemesterSQL) WHERE ID=$degree";
                SQLiteManager::getInstance()->query($sql);
            }
        }
    }
?>