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
    if($_GET["reset"] == 1) {
        session_destroy();
        session_write_close();
        die("reset");
    }
    require_once("functions.php");
    require_once("SQLiteManager.php");
    require_once("CourseSequence.php");
    require_once("ClassList.php");

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
        $data = getCache("http://".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi");
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
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
    $minors = getMinors($yearKey);

    //course substitutions
    if(isset($_REQUEST["substitute"])) {
        substituteClass($db, $userID, $_REQUEST["orig"], $_REQUEST["sub"]);
    }

    if(isset($_REQUEST["reset"])) {
        $db->query("DELETE FROM userClassMap WHERE userID=".$userID);
    }

    //get the list of classes the user is already enrolled in and their currently declared degree(s)
    if(empty($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="LETU Login"');
        header('HTTP/1.0 401 Unauthorized');
        require("privacy.php?hideBack=1");
        die();
    } else {
        list($courses, $degree) = getUserInfo($majors);
    }

    $degOptions = array_merge($majors, $minors);
    $degrees = array();
    foreach($degree as $deg) {
        $degrees[] = $degOptions[$deg];
    }

    $masterCourses = $courses;
    $courseSequences = array();
    $substitute = clone $masterCourses;
    $substituteCandidates = new ClassList();
    foreach($degrees as $deg) {
        $courses = clone $masterCourses;
        $courseSequence = CourseSequence::getFromID($deg["ID"]);
        $courseSequence->evalTaken($courses, $_SESSION["userID"]);
        $courseSequences[] = $courseSequence;

        $tmp = ClassList::diff($courses, $masterCourses);
        $substitute = ClassList::intersect($substitute, $tmp);

        $substituteCandidates = ClassList::merge($substituteCandidates, $courseSequence->getIncompleteClasses());
    }
    $class = Course::getFromDepartmentNumber("LETU", "4999", "Transfer Credit");
    $substitute[$class->getID()] = $class;

require_once("header.php");
//header form
    print '<form method="get" action=".">';
        print 'Year: <select name="year">';
            foreach($years as $yr) {
                print "<option value='$yr'";
                if($yr == $year) {
                    print " selected='selected'";
                }
                print ">".$yr."</option>";
            }
        print "</select>";
        print "<br/>";

        print '<select name="degree[]" size="5" multiple="multiple">';
            print '<optgroup label="-- Majors">';
                foreach($majors as $key=>$deg) {
                    print '<option value="'.$key.'"';
                    if(is_array($degree) && in_array($key, $degree)) {
                        print " selected='selected'";
                    }
                    print '>'.$deg["name"].' ('.$key.')</option>';
                }
            print '</optgroup>';
            print '<optgroup label="-- Minors">';
                foreach($minors as $key=>$deg) {
                    print '<option value="'.$key.'"';
                    if(is_array($degree) && in_array($key, $degree)) {
                        print " selected='selected'";
                    }
                    print ' disabled="disabled">'.$deg["name"].' ('.$key.')</option>';
                }
            print '</optgroup>';
        print "</select>";
        print "<br/>";

        print '<input type="submit" name="submit" value="submit"/>';
    print "</form>";
    print "<br/>";

//    print '<img src="images/image.php">';
//    print '<br>';

//display courses
    foreach($courseSequences as $courseSequence) {
        if(empty($_GET["disp"]) || $_GET["disp"] == "summary") {
            $courseSequence->display();
        } elseif($_GET["disp"] == "list") {
            $courseSequence->displayRequirementsList();
        }
        print "<br/>";
    }

//footer form
    print '<form method="post" action="'.$_SERVER["REQUEST_URI"].'">';
        print 'Substitute ';
        displayClassSelect("sub", $substitute);
        print ' for ';
        displayClassSelect("orig", $substituteCandidates);
        print '<br/>';
        print '<input type="submit" name="substitute" value="Substitute">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;';
        print '<input type="submit" name="reset" value="Reset">';
    print '</form>';
require_once("footer.php");
?>