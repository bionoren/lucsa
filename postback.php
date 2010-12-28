<?php
    session_start();
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
        $course = Course::getFromDepartmentNumber($_REQUEST["dept"], $_REQUEST["num"], $_REQUEST["title"]);
        $target = explode("~", $_REQUEST["target"]);
        substituteClass($_SESSION["userID"], $_REQUEST["degree"], $target[0], $course->getID());
        $smarty = new Smarty();
        $smarty->assign("class", $course);
        $smarty->display("course_sub.tpl");
    }

    if($mode == "uncompleteClass") {
        $target = explode("~", $_REQUEST["target"]);
        substituteClass($_SESSION["userID"], $_REQUEST["degree"], $target[0]);
    }

    if($mode == "getClassFromDeptNum") {
        $course = Course::getFromDepartmentNumber($_REQUEST["dept"], $_REQUEST["num"], $_REQUEST["title"]);
        if($_REQUEST["needDept"] == "true") {
            print '<div class="deptHeader">'.$_REQUEST["dept"].'</div>';
        }
        print '<div id="'.$course->getUID().'" class="incompleteClass">';
            $smarty = new Smarty();
            $smarty->assign("class", $course);
            $smarty->display("course_sub.tpl");
        print '</div>';
    }
?>