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

    require_once("functions.php");
    require_once("SQLiteManager.php");
    session_start();

    $year = intval($_REQUEST["year"])+1;
    if(isset($_REQUEST["degree"])) {
        $degree = $_REQUEST["degree"];
    }

    //get all the degree options
    $db = new SQLiteManager("lucsa.sqlite");
    $years = getYears($db);
    $majors = getMajors($db, $year);
    $minors = getMinors($db, $year);

    if (empty($degree) && empty($_SERVER['PHP_AUTH_USER'])) {
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
            mdecrypt_generic($sec, base64_decode("R9GhWOS8EHhX7n0tp9kxQQM+vd6og2BvXF4wxxelSN81FCDZ2Kww9tE5RyAW5MXUDNmm3js2k1nAKHvs6ohod1K+sYruQFCk+mVidrLvveC+JuxK+zB42DNmH5IHGCup"));
            $degree = explode("~", trim(mdecrypt_generic($sec, $_SESSION["degree"])));
            $courses = explode("~", trim(mdecrypt_generic($sec, $_SESSION["courses"])));
        } else {
            $data = file_get_contents("http://".$_SERVER['PHP_AUTH_USER'].":".$_SERVER['PHP_AUTH_PW']."@cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi");
            $data = preg_replace("/^.*?Undergraduate Program/is", "", $data);
            $matches = array();
            preg_match("/(?:\<td.*?){3}.*?\>(.*?)\<.*?\>(.*?)\</is", $data, $matches);
            array_shift($matches);
            foreach($matches as $match) {
                $match = trim($match);
                if(!empty($match)) {
                    $tmp = guessMajor($majors, $match);
                    $degree[] = $tmp;
                }
            }
            $matches = array();
            preg_match_all("/\<td.*?\>(?P<dept>\w{4})(?P<course>\d{4})\s*\</is", $data, $matches, PREG_SET_ORDER);
            $courses = array();
            foreach($matches as $matchset) {
                $courses[] = $matchset["dept"].$matchset["course"];
            }

            //the first block was getting corrupt for some reason, so I'm filling it with junk
            mcrypt_generic($sec, getKeyStr());
            $_SESSION["degree"] = mcrypt_generic($sec, implode("~", $degree));
            $_SESSION["courses"] = mcrypt_generic($sec, implode("~", $courses));
            unset($data);
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
    $allCourses = getCourses($db, $tmp);

require_once("header.php");
    print '<form method="get" action=".">';
        print 'Year: <select name="year">';
            foreach($years as $key=>$yr) {
                print "<option value='$key'";
                if($key == $year-1) {
                    print " selected='selected'";
                }
                print ">".$yr[0]."</option>";
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

    displayCourseSequence($years[$year-1][0], $allCourses, $courses);
require_once("footer.php");
?>