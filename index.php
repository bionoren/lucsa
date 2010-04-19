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

    $year = intval($_REQUEST["year"])+1;
    if(isset($_REQUEST["degree"])) {
        $degree = $_REQUEST["degree"];
    }

    //get all the degree options
    $db = SQLiteManager::getInstance();

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

    if(isset($_REQUEST["substitute"])) {
        $subID = $_REQUEST["sub"];
        $origID = $_REQUEST["orig"];

        $sql = "DELETE FROM userClassMap WHERE userID=".$userID." AND oldClassID=".$origID;
        $db->query($sql);

        $fields["userID"] = $userID;
        $fields["oldClassID"] = $origID;
        $fields["newClassID"] = $subID;
        $db->insert("userClassMap", $fields);
    }

    $years = getYears();
    $majors = getMajors($year);
    $minors = getMinors($year);

    if(empty($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="LETU Login"');
        header('HTTP/1.0 401 Unauthorized');
        require("privacy.php?hideBack=1");
        die();
    } else {
        //Advanced Encryption Standard (AES) 256 with Cipher Block Chaining (CBC)
        $sec = mcrypt_module_open("rijndael-256", "", "cbc", "");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($sec), MCRYPT_DEV_RANDOM);
        $key = substr(md5(getKeyStr()), 0, mcrypt_enc_get_key_size($sec));
        mcrypt_generic_init($sec, $key, $iv);
        if(isset($_SESSION["degree"])) {
            //don't ask. Just don't ask...
            mdecrypt_generic($sec, base64_decode($_SESSION["rand"]));
            $degree = explode("~", trim(mdecrypt_generic($sec, $_SESSION["degree"])));
            $coursArray = explode("~", trim(mdecrypt_generic($sec, $_SESSION["courses"])));
            $courses = array();
            foreach($coursArray as $item) {
                $class = unserialize($item);
                $courses[$class->getDepartment().$class->getNumber()] = $class;
            }
        } else {
            $data = file_get_contents("http://".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi");
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
            $courses = array();
            foreach($matches as $matchset) {
                $class = Course::getFromDepartmentNumber($year, $matchset["dept"], $matchset["course"], $matchset["title"]);
                $courses[$class->getID()] = $class;
                $storeCourses[] = serialize($class);
            }

            //the first block was getting corrupt for some reason, so I'm filling it with junk
            $_SESSION["rand"] = base64_encode(mcrypt_generic($sec, getKeyStr()));
            $_SESSION["degree"] = mcrypt_generic($sec, implode("~", $degree));
            $_SESSION["courses"] = mcrypt_generic($sec, implode("~", $storeCourses));
        }
        mcrypt_generic_deinit($sec);
        mcrypt_module_close($sec);
    }
//    dump("degree", $degree);
//    dump("courses", $courses);

    $degOptions = array_merge($majors, $minors);
    $tmp = array();
    foreach($degree as $deg) {
        $tmp[] = $degOptions[$deg];
    }
//    dump("tmp", $tmp);
//    $allCourses = getCourses($tmp);
//    dump("courses", $allCourses);

    $temp = current($tmp);
    $courseSequence = CourseSequence::getFromID($temp["ID"]);
    $class = Course::getFromDepartmentNumber($year, "LETU", "4999", "Transfer Credit");
    $courses[$class->getID()] = $class;
    $courseSequence->evalTaken($courses, $_SESSION["userID"]);

require_once("header.php");
    print '<form method="get" action=".">';
        print 'Year: <select name="year">';
            foreach($years as $key=>$yr) {
                print "<option value='$key'";
                if($key == $year-1) {
                    print " selected='selected'";
                }
                print ">".$yr."</option>";
            }
        print "</select>";
        print "<br/>";

        print '<select name="degree[]" size="5" multiple="multiple">';
            print '<optgroup label="-- Majors"';
                foreach($majors as $key=>$deg) {
                    print '<option value="'.$key.'"';
                    if(is_array($degree) && in_array($key, $degree)) {
                        print " selected='selected'";
                    }
                    print '>'.$deg["name"].' ('.$key.')</option>';
                }
            print '</optgroup>';
            print '<optgroup label="-- Minors"';
                foreach($minors as $key=>$deg) {
                    print '<option value="'.$key.'"';
                    if(is_array($degree) && in_array($key, $degree)) {
                        print " selected='selected'";
                    }
                    print '>'.$deg["name"].' ('.$key.')</option>';
                }
            print '</optgroup>';
        print "</select>";
        print "<br/>";

        print '<input type="submit" name="submit" value="submit"/>';
    print "</form>";
    print "<br>";

    $courseSequence->display();
    print '<form method="post" action=".">';
        print 'Substitute ';
        print '<select name="sub">';
            ksort($courses);
            $dept = null;
            foreach($courses as $class) {
                if($class->getDepartment() != $dept) {
                    $dept = $class->getDepartment();
                    print '<optgroup label="'.$dept.'">';
                }
                print '<option value="'.$class->getID().'"';
                if($class->isComplete()) {
                    print ' style="color:rgb(177, 177, 177);"';
                }
                print '>'.$class->getTitle().'</option>';
            }
        print '</select>';
        print ' for ';
        print '<select name="orig">';
            $temp = $courseSequence->getIncompleteClasses();
            ksort($temp);
            $dept = null;
            foreach($temp as $class) {
                if($class->getDepartment() != $dept) {
                    $dept = $class->getDepartment();
                    print '<optgroup label="'.$dept.'">';
                }
                print '<option value="'.$class->getID().'">'.$class->getTitle().'</option>';
            }
        print '</select>';
        print '<br>';
        print '<input type="submit" name="substitute" value="Substitute">';
    print '</form>';
require_once("footer.php");
?>