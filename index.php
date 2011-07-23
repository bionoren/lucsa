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

    session_start();
    date_default_timezone_set("America/Chicago");
    $path = "./";

    require_once($path."smarty/Smarty.class.php");
    require_once($path."functions.php");
    require_once($path."Tab.php");
    require_once($path."Main.php");

    /**
     * Encrypts the given string using the specified hashing algorithm.
     *
     * @param STRING $string The string to encrypt.
     * @param STRING $salt Salt to use on the given string
     * @return STRING Encrypted text (usually significantly longer than the input text).
     */
    function encrypt($string, $salt) {
        return hash("sha512", $salt.$string);
    }

    /**
     * Generates a pseudo-random string for use with password hashing.
     *
     * @return STRING A pseudo-random 32 character string.
     */
    function generateSalt() {
        return md5(mt_rand()*M_LOG2E);
    }

    /**
     * Retrieves the current user's information from the session.
     *
     * @return ARRAY array(CourseList courses, array degree).
     */
    function getUserInfoFromSession() {
        $temp = explode("~", $_SESSION["degree"]);
        if(isset($_REQUEST["degree"])) {
            $degree = $_REQUEST["degree"];
        } else {
            $degree = $temp;
        }
        $courses = unserialize($_SESSION["courses"]);

        return array($courses, $degree);
    }

    /**
     * Retrieves the current user's information from their academic record,
     * stores it in the session, and cleans up any unneeded information.
     *
     * @param STRING $username LETU username.
     * @param STRING $password LETU password.
     * @return ARRAY array(CourseList courses, array degree)
     */
    function storeUserInfo($username, $password) {
        //SECURITY NOTE: Remove this in production!
//        $data = getCache("saved_cache/stugrdsa.cgi.html", false);
        $data = getCache("https://".$username.":".$password."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi", false);
        unset($_REQUEST["username"]);
        unset($_REQUEST["password"]);
        if(empty($data)) {
            return false;
        }
        $data = preg_replace("/^.*?Undergraduate Program/is", "", $data);
        $matches = array();
        preg_match("/(?:\<td.*?){3}.*?\>(.*?)\<.*?\>(.*?)\</is", $data, $matches);
        array_shift($matches);
        $degree = array();
        foreach($matches as $match) {
            $match = trim($match);
            if(!empty($match)) {
                $tmp = guessMajor($match);
                $degree[] = $tmp;
            }
        }
        $matches = array();
        preg_match_all("/\<td[^\>]*\>(?P<dept>\w{3,4})(?P<course>\d{3,4})(?:[^\<]*\<td[^\>]*\>){2}(?P<title>[^\<]*?)\s*\<td/is", $data, $matches, PREG_SET_ORDER);
        unset($data);
        $courses = new ClassList();
        foreach($matches as $matchset) {
            $class = Course::getFromDepartmentNumber($matchset["dept"], $matchset["course"], $matchset["title"]);
            if($class != null) {
                $courses[$class->ID] = $class;
            }
        }

        $_SESSION["degree"] = implode("~", $degree);
        $_SESSION["courses"] = serialize($courses);

        return array($courses, $degree);
    }

    /**
     * Retrieves the current user's information.
     *
     * @return ARRAY array(CourseList courses, array degree)
     */
    function getUserInfo() {
        if(isset($_SESSION["degree"])) {
            $ret = getUserInfoFromSession();
        } elseif(isset($_REQUEST["login"])) {
            $ret = storeUserInfo($_REQUEST["username"], $_REQUEST["password"]);
            if(!$ret) {
                $ret = array(new ClassList(), array());
            } else {
                Main::getInstance()->activateUser();
            }
        } else {
            $ret = array(new ClassList(), array());
        }

        return $ret;
    }

    //==========================================================================
    //                      END FUNCTION DEFINITIONS
    //==========================================================================
    $main = Main::getInstance();

    if(isset($_POST["clearClasses"])) {
        SQLiteManager::getInstance()->query("DELETE FROM userClassMap WHERE userID='".$main->userID."'");
    }

    //get the list of classes the user is already enrolled in and their currently declared degree(s)
    list($masterCourses, $degree) = getUserInfo();

    $tabs = Tab::getFromDB();
    if(empty($tabs)) {
        $tabs[] = Tab::getFromID();
    }
    $tabDegrees = $tabs[0]->degrees;
    if(empty($tabDegrees)) {
        foreach($degree as $deg) {
            $tabs[0]->addDegree($main->majors[$deg]["ID"]);
        }
    }
    if(isset($_REQUEST["degree"])) {
        $tabs[0]->clearDegrees();
        foreach($_REQUEST["degree"] as $degree) {
            $tabs[0]->addDegree($main->majors[$degree]["ID"]);
        }
    }
    foreach($tabs as $tab) {
        $tab->setClassesTaken($masterCourses);
        $tab->finalize(isset($_REQUEST["autocomplete"]));
    }

    $smarty = new Smarty();
    $data = new Smarty_Data();
    $data->assign("year", $main->year);
    $data->assign("years", $main->years);
    $data->assign("majors", $main->majors);
    $data->assign("tabs", $tabs);
    $data->assign("activated", Main::getInstance()->activated);

    $smarty->display("index.tpl", $data);
?>