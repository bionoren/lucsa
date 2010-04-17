<?php
    require_once("SQLiteManager.php");
    require_once("functions.php");

    $db = SQLiteManager::getInstance();

    //catalogs
    $fields = array();
    $field = new DBField("year", DBField::NUM);
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("updated", DBField::NUM, 0); //nothing, deep, majors, both
    $db->createTable("years", $fields);

    //degrees
    $fields = array();
    $fields[] = new DBField("yearID", DBField::NUM, null, "years");
    $fields[] = new DBField("linkid", DBField::NUM);
    $fields[] = new DBField("name", DBField::STRING);
    $fields[] = new DBField("acronym", DBField::STRING);
    $fields[] = new DBField("type", DBField::NUM); //none, major, minor
    $fields[] = new DBField("numSemesters", DBField::NUM, 0);
    $db->createTable("degrees", $fields);
    $db->createUniqueConstraint("degrees", array($fields[0], $fields[3]));

    //departments
    $fields = array();
    $field = new DBField("department", DBField::STRING);
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("linkid", DBField::NUM);
    $db->createTable("departments", $fields);

    //classes
    $fields = array();
    $fields[] = new DBField("departmentID", DBField::NUM, null, "departments", "ID");
    $fields[] = new DBField("yearID", DBField::NUM, -1, "years", "ID");
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("endNumber", DBField::NUM); //only used for ranges, like special topics classes
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("linkid", DBField::NUM);
    $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
    $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
    $fields[] = new DBField("hours", DBField::NUM, 3);
    $db->createTable("classes", $fields);
    //department, year, and number
    $db->createUniqueConstraint("classes", array($fields[0], $fields[1], $fields[2], $fields[3]));
    //year and title
    $db->createUniqueConstraint("classes", array($fields[1], $fields[4]));

    //class prerequisites and corequisites
    $fields = array();
    $fields[] = new DBField("classID", DBField::NUM, "-1", "classes");
    $fields[] = new DBField("requiresClassID", DBField::NUM, null, "classes");
    $fields[] = new DBField("type", DBField::NUM, 0); //none, prereq, coreq, either
    //we might not have added this class yet, so these will have to be evaluated to a requiresClassID in a future pass
    $fields[] = new DBField("department", DBField::STRING);
    $fields[] = new DBField("courseNumber", DBField::NUM);
    $db->createTable("classDependencyMap", $fields);

    //mapping classes to degrees
    $fields = array();
    $fields[] = new DBField("degreeID", DBField::NUM, -1, "degrees");
    $fields[] = new DBField("courseID", DBField::NUM, -1, "classes");
    $fields[] = new DBField("semester", DBField::NUM);
    $fields[] = new DBField("notes", DBField::STRING);
    $db->createTable("degreeCourseMap", $fields);

    //users
    $fields = array();
    $field = new DBField("user", DBField::STRING); //hash of username
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("salt", DBField::STRING);
    $fields[] = new DBField("majors", DBField::STRING);
    $fields[] = new DBField("minors", DBField::STRING);
    $db->createTable("users", $fields);

    //course subsitutions for individual users
    $fields = array();
    $fields[] = new DBField("userID", DBField::NUM, -1, "users");
    $fields[] = new DBField("oldClassID", DBField::NUM, -1, "classes");
    $fields[] = new DBField("newClassID", DBField::NUM, -1, "classes");
    $db->createTable("userClassMap", $fields);
    $db->createUniqueConstraint("userClassMap", array($fields[0], $fields[1]));

    //initialize the years
    $data = getCache("http://www.letu.edu/academics/catalog/");
    $matches = array();
    preg_match_all("/\>(\d{4})-\d{4}\</is", $data, $matches, PREG_PATTERN_ORDER);
    $years = $matches[1];
    foreach($years as $year) {
        $db->insert("years", array("year"=>$year));
    }
?>