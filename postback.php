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
    require_once($path."Course.php");

    $mode = $_REQUEST["mode"];
    if(isset($_REQUEST["data"])) {
        $data = $_REQUEST["data"];
        parse_str($data, $tmp);
        $_REQUEST = array_merge($tmp, $_REQUEST);
        unset($_REQUEST["data"]);
    }

    if($mode == "completeClass") {
        $id = intval($_REQUEST["ID"]);
        $course = Course::getFromID($id);
        substituteClass($_SESSION["userID"], intval($_REQUEST["target"]), $id);
        $smarty = new Smarty();
        $smarty->assign("class", $course);
        $smarty->display("course_sub.tpl");
    }

    if($mode == "uncompleteClass") {
        substituteClass($_SESSION["userID"], intval($_REQUEST["classID"]));

        $takenCourse = Course::getFromID($_REQUEST["takenClassID"]);
        if($_REQUEST["needDept"] == "true") {
            print '<div class="deptHeader">'.$takenCourse->getDepartment().'</div>';
        }
        print '<div id="'.$takenCourse->getUID().'" class="incompleteClass">';
            $smarty = new Smarty();
            $smarty->assign("class", $takenCourse);
            $smarty->display("course_sub.tpl");
        print '</div>';
    }

    if($mode == "moveClass") {
        moveClass($_REQUEST["degree"], $_REQUEST["oldSemester"], $_REQUEST["semester"], $_REQUEST["course"]);
    }
?>