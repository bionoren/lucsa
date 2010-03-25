<?php
    require_once("SQLiteManager.php");

    $db = new SQLiteManager("lucsa.sqlite");

    //catalogs
    $fields = array();
    $field = new DBField("year", DBField::NUM);
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("updated", DBField::NUM, "0"); //nothing, deep, majors, both
    $db->createTable("years", $fields);

    //majors
    $fields = array();
    $fields[] = new DBField("yearID", DBField::NUM, null, "years");
    $fields[] = new DBField("linkid", DBField::NUM);
    $fields[] = new DBField("name", DBField::STRING);
    $fields[] = new DBField("acronym", DBField::STRING);
    $fields[] = new DBField("type", DBField::NUM); //none, major, minor
    $db->createTable("degrees", $fields);

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
    $fields[] = new DBField("departmentID", DBField::NUM, "-1", "departments", "ID");
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("linkid", DBField::NUM);
    $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
    $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
    $fields[] = new DBField("hours", DBField::NUM, 3);
    $db->createTable("classes", $fields);
    $db->createUniqueConstraint("classes", array($fields[0], $fields[1]));

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
    $fields[] = new DBField("degreeID", DBField::NUM, "-1", "degrees");
    $fields[] = new DBField("courseID", DBField::NUM, "-1", "classes");
    $fields[] = new DBField("semester", DBField::NUM);
    $fields[] = new DBField("notes", DBField::STRING);
    $db->createTable("degreecoursemap", $fields);

    //users
    $fields = array();
    $field = new DBField("user", DBField::STRING); //hash of username
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("major1", DBField::NUM, null, "degrees");
    $fields[] = new DBField("major2", DBField::NUM, null, "degrees");
    $fields[] = new DBField("minor1", DBField::NUM, null, "degrees");
    $fields[] = new DBField("minor2", DBField::NUM, null, "degrees");
    $db->createTable("users", $fields);

    //course subsitutions for individual users
    $fields = array();
    $fields[] = new DBField("userID", DBField::NUM, "-1", "users");
    $fields[] = new DBField("oldClassID", DBField::NUM, "-1", "classes");
    $fields[] = new DBField("newClassID", DBField::NUM, "-1", "classes");
    $db->createTable("userclassmap", $fields);

    //initialize the years
    $data = file_get_contents("http://www.letu.edu/academics/catalog/");
    $matches = array();
    preg_match_all("/\>(\d{4})-\d{4}\</is", $data, $matches, PREG_PATTERN_ORDER);
    $years = $matches[1];
    foreach($years as $year) {
        $db->insert("years", array("year"=>$year));
    }
?>