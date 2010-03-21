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

	//DEBUGGING FUNCTIONS
	function dump($name, $array, $member=null) {
		if(!is_array($array)) {
			print "$name = $array<br/>";
		} else {
			foreach($array as $key=>$val) {
				if(is_array($val)) {
                    if($member == null)
    					dump($name."[$key]", $val, $member);
                    else
                        dump($name."[$key]", $val);
                } else {
                    if($member == null) {
    					print $name."[".$key."] = ".$val."<br/>";
                    } else {
                        print $name."[".$key."] = ".$val->{$member}()."<br/>";
                    }
                }
			}
		}
	}

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

    function getYears(SQLiteManager $db) {
        $result = $db->query("SELECT * from years");
        $years = array();
        while($years[] = $result->fetchArray(SQLITE3_NUM));
        array_pop($years);
        return $years;
    }

    function getMajors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT * FROM degrees WHERE yearID='".$year."' AND type='1'");
        $majors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $majors[$row["acronym"]] = $row;
        }
        return $majors;
    }

    function getMinors(SQLiteManager $db, $year) {
        $result = $db->query("SELECT * FROM degrees WHERE yearID='".($year+1)."' AND type='2'");
        $minors = array();
        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $minors[$row["acronym"]] = $row;
        }
        return $minors;
    }

    function getKeyStr() {
        return "Curse:DuckInADungeon. You should know better than to pick up a duck in a dungeon.";
    }
?>