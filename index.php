<?php
    /*
     *	Copyright 2009 Bion Oren
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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
    <head>
        <title>LUCSA</title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta http-equiv="content-language" content="en"/>
        <meta name="language" content="en"/>
        <meta name="description" content="Helps LETU students figure out their major and course sequence"/>
        <meta name="keywords" content="LETU LeTourneau student schedule class classes course sequence major minor"/>
    </head>
    <body lang="en">
        <?php
            $year = intval($_REQUEST["year"]);
            $degree = $_REQUEST["degree"];

            $db = new SQLiteManager("lucsa.sqlite");
            $result = $db->query("SELECT * from years");
            $years = array();
            while($years[] = $result->fetchArray(SQLITE3_NUM));
            array_pop($years);

            $result = $db->query("SELECT * FROM degrees WHERE yearID='".($year+1)."' AND type='1'");
            $majors = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $majors[$row["acronym"]] = $row;
            }
            $result = $db->query("SELECT * FROM degrees WHERE yearID='".($year+1)."' AND type='2'");
            $minors = array();
            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $minors[$row["acronym"]] = $row;
            }

            print '<form method="get" action=".">';
                print 'Year: <select name="year">';
                    foreach($years as $key=>$yr) {
                        print "<option value='$key'";
                        if($key == $year) {
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
        ?>
    </body>
</html>