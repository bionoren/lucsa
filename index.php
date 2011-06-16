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
    require_once($path."db/SQLiteManager.php");
    require_once($path."functions.php");
    require_once($path."Tab.php");

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
     * @param RESOURCE $sec Encryption descriptor.
     * @return ARRAY array(CourseList courses, array degree).
     */
    function getUserInfoFromSession($sec) {
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
     * @param RESOURCE $sec Encryption descriptor.
     * @param ARRAY $majors List of possible majors.
     * @return ARRAY array(CourseList courses, array degree)
     */
    function storeUserInfo($sec, array $majors) {
        //SECURITY NOTE: Remove this in production!
        $data = getCache("saved_cache/stugrdsa.cgi.html", false);
//        $data = getCache("https://".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi", false);
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
        if(empty($data)) {
            requestLogin();
        }
        $data = preg_replace("/^.*?Undergraduate Program/is", "", $data);
        $matches = array();
        preg_match("/(?:\<td.*?){3}.*?\>(.*?)\<.*?\>(.*?)\</is", $data, $matches);
        array_shift($matches);
        $degree = array();
        foreach($matches as $match) {
            $match = trim($match);
            if(!empty($match)) {
                $tmp = guessMajor($majors, $match);
                $degree[] = $tmp;
            }
        }
        $matches = array();
        preg_match_all("/\<td.*?\>(?P<dept>\w{4})(?P<course>\d{4})(?:\s*\<.*?\<.*?\>){2}(?P<title>.*?)\s*\</is", $data, $matches, PREG_SET_ORDER);
        unset($data);
        $courses = new ClassList();
        foreach($matches as $matchset) {
            $class = Course::getFromDepartmentNumber($matchset["dept"], $matchset["course"], $matchset["title"]);
            if($class != null) {
                $courses[$class->getID()] = $class;
            }
        }

        $_SESSION["degree"] = implode("~", $degree);
        $_SESSION["courses"] = serialize($courses);

        return array($courses, $degree);
    }

    /**
     * Retrieves the current user's information.
     *
     * @param ARRAY $majors List of possible majors.
     * @return ARRAY array(CourseList courses, array degree)
     */
    function getUserInfo(array $majors) {
        if(isset($_SESSION["degree"])) {
            $ret = getUserInfoFromSession($sec);
        } else {
            $ret = storeUserInfo($sec, $majors);
        }

        return $ret;
    }

    /**
     * Ask the user to logon.
     *
     * @return VOID
     */
    function requestLogin() {
        global $path;
        header('WWW-Authenticate: Basic realm="LETU Login"');
        header('HTTP/1.0 401 Unauthorized');
        require($path."privacy.php");
        die();
    }

    //==========================================================================
    //                      END FUNCTION DEFINITIONS
    //==========================================================================
    $db = SQLiteManager::getInstance();
    $userID = getUserID($db);

    if(isset($_POST["clearClasses"])) {
        $db->query("DELETE FROM userClassMap WHERE userID='".$userID."'");
    }

    $years = getYears();
    if(!isset($_REQUEST["year"])) {
        $yearKey = current(array_keys($years));
        $year = $years[$yearKey];
    } else {
        $year = intval($_REQUEST["year"]);
        $yearKey = array_search($year, $years);
    }
    //get all the degree options
    $majors = getMajors($yearKey);

    //get the list of classes the user is already enrolled in and their currently declared degree(s)
    if(isset($_SERVER['HTTP_AUTHORIZATION']) && empty($_SERVER['PHP_AUTH_USER'])) {
        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
    }
    if(empty($_SERVER['PHP_AUTH_USER'])) {
        requestLogin();
    } else {
        list($masterCourses, $degree) = getUserInfo($majors);
    }

    $tabs = Tab::getFromDB($userID);
    if(empty($tabs)) {
        $tab = Tab::getFromID();
        foreach($degree as $deg) {
            $tab->addDegree($majors[$deg]["ID"]);
        }
        $tabs[] = $tab;
    }
    foreach($tabs as $tab) {
        $tab->setClassesTaken($masterCourses);
        $tab->finalize(isset($_REQUEST["autocomplete"]));
    }

    $smarty = new Smarty();
    $data = new Smarty_Data();
    $data->assign("year", $year);
    $data->assign("years", $years);
    $data->assign("degree", $degree);
    $data->assign("majors", $majors);
    $data->assign("tabs", $tabs);

    $smarty->display("index.tpl", $data);
?>