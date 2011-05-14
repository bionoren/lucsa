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
//--DEBUG
    if($_GET["reset"] == 1) {
        require_once($path."db/SQLiteManager.php");
        require_once($path."functions.php");
        $db = SQLiteManager::getInstance();
        $id = getUserID($db);
        $db->query("DELETE FROM users WHERE ID='".$id."'");
        $db->query("DELETE FROM userClassMap WHERE userID='".$id."'");
        session_destroy();
        session_write_close();
        die("reset");
    }
//--END DEBUG
    require_once($path."smarty/Smarty.class.php");
    require_once($path."functions.php");
    require_once($path."db/SQLiteManager.php");
    require_once($path."CourseSequence.php");
    require_once($path."ClassList.php");
    require_once($path."Autocompleter.php");

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
        //don't ask. Just don't ask...
        mdecrypt_generic($sec, base64_decode($_SESSION["rand"]));
        $temp = explode("~", trim(mdecrypt_generic($sec, $_SESSION["degree"])));
        if(isset($_REQUEST["degree"])) {
            $degree = $_REQUEST["degree"];
        } else {
            $degree = $temp;
        }
        $courses = unserialize(trim(mdecrypt_generic($sec, $_SESSION["courses"])));

        return array($courses, $degree);
    }

    /**
     * Retrieves the current user's information from their academic record, encrypts the data,
     * stores it in the session, and cleans up any unneeded information.
     *
     * @param RESOURCE $sec Encryption descriptor.
     * @param ARRAY $majors List of possible majors.
     * @return ARRAY array(CourseList courses, array degree)
     */
    function storeUserInfo($sec, array $majors) {
        //SECURITY NOTE: Remove this in production!
        $data = getCache("https://".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi", false);
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
        preg_match_all("/\<td.*?\>(?P<dept>\w{4})(?P<course>\d{4})\s*\<.*?\<.*?\>(?P<title>.*?)\s*\</is", $data, $matches, PREG_SET_ORDER);
        unset($data);
        $courses = new ClassList();
        foreach($matches as $matchset) {
            $class = Course::getFromDepartmentNumber($matchset["dept"], $matchset["course"], $matchset["title"]);
            if($class != null) {
                $courses[$class->getID()] = $class;
            }
        }

        //the first block was getting corrupt for some reason, so I'm filling it with junk
        $_SESSION["rand"] = base64_encode(mcrypt_generic($sec, getKeyStr()));
        $_SESSION["degree"] = mcrypt_generic($sec, implode("~", $degree));
        $_SESSION["courses"] = mcrypt_generic($sec, serialize($courses));

        return array($courses, $degree);
    }

    /**
     * Retrieves the current user's information.
     *
     * @param ARRAY $majors List of possible majors.
     * @return ARRAY array(CourseList courses, array degree)
     */
    function getUserInfo(array $majors) {
        //Advanced Encryption Standard (AES) 256 with Cipher Block Chaining (CBC)
        $sec = mcrypt_module_open("rijndael-256", "", "cbc", "");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($sec), MCRYPT_DEV_RANDOM);
        $key = substr(md5(getKeyStr()), 0, mcrypt_enc_get_key_size($sec));
        mcrypt_generic_init($sec, $key, $iv);
        if(isset($_SESSION["degree"])) {
            $ret = getUserInfoFromSession($sec);
        } else {
            $ret = storeUserInfo($sec, $majors);
        }
        mcrypt_generic_deinit($sec);
        mcrypt_module_close($sec);

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
    $transferClass = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
    $masterCourses[$transferClass->getID()] = $transferClass;

    $degOptions = $majors;
    $degrees = array();
    foreach($degree as $deg) {
        $degrees[] = $degOptions[$deg]["ID"];
    }

    $courseSequence;
    $substitute = new ClassList();
    $user = $_SESSION["userID"];

    foreach($degrees as $deg) {
        $courses = clone $masterCourses;
        $courseSequence = CourseSequence::getFromID($deg);
        $courseSequence->applySubstitions($courses, $user);
        $courseSequences[] = $courseSequence;

        $substitute = ClassList::merge($substitute, $courses->filter(function(Course $class) { return !$class->isSubstitute; }));
        break;
    }
    if(isset($_REQUEST["autocomplete"])) {
        foreach($courseSequences as $cs) {
            $autocompleter = new Autocompleter($substitute, $cs->getClasses(), $cs->getNotes());
            $autocompleter->substitute($user);
        }
        foreach($courseSequences as $cs) {
            $courses = clone $masterCourses;
            $cs->applySubstitions($courses, $user);

            $substitute = ClassList::merge($substitute, $courses->filter(function(Course $class) { return !$class->isSubstitute; }));
            break;
        }
    }

    $courses[$transferClass->getID()]->isSubstitute = false;
    $substitute[$transferClass->getID()] = $transferClass;
    $substitute->sort();

    $smarty = new Smarty();
    $data = new Smarty_Data();
    $data->assign("year", $year);
    $data->assign("years", $years);
    $data->assign("degree", $degree);
    $data->assign("majors", $majors);
    $data->assign("subClasses", $substitute);
    $data->assign("courseSequences", $courseSequences);

    $smarty->display("index.tpl", $data);
?>