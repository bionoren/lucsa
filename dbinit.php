<?php
    require_once("SQLiteManager.php");

    $db = new SQLiteManager("lucsa.sqlite");
    //catalogs
    $fields = array();
    $field = new DBField("year", DBField::NUM);
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("updated", DBField::NUM, 0);
    $db->createTable("years", $fields);
    //majors
    $fields = array();
    $fields[] = new DBField("yearID", DBField::NUM, null, "years", "ROWID");
    $fields[] = new DBField("id", DBField::NUM);
    $fields[] = new DBField("name", DBField::STRING);
    $fields[] = new DBField("acronym", DBField::STRING);
    $fields[] = new DBField("type", DBField::NUM); //none, major, minor
    $db->createTable("degrees", $fields);
    //departments
    $fields = array();
    $field = new DBField("department", DBField::NUM);
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("id", DBField::NUM);
    $db->createTable("departments", $fields);
    //classes
    $fields = array();
    $fields[] = new DBField("degreeID", DBField::NUM, null, "degrees", "ROWID");
    $fields[] = new DBField("departmentID", DBField::NUM, -1, "departments", "ROWID");
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("id", DBField::STRING);
    $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
    $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
    $fields[] = new DBField("hours", DBField::NUM, 3);
    $fields[] = new DBField("extra", DBField::STRING);
    $db->createTable("classes", $fields);
    $db->createUniqueConstraint("classes", array($fields[1], $fields[2]));

    $fields = array();
    $fields[] = new DBField("degreeID", DBField::NUM, -1, "degrees", "ROWID");
    $fields[] = new DBField("courseID", DBField::NUM, -1, "classes", "ROWID");
    $fields[] = new DBField("semester", DBField::NUM);
    $db->createTable("degreecoursemap", $fields);

    $fields = array();
    $field = new DBField("user", DBField::STRING); //hash of username
    $field->setUnique();
    $fields[] = $field;
    $fields[] = new DBField("major1", DBField::NUM, null, "degrees", "ROWID");
    $fields[] = new DBField("major2", DBField::NUM, null, "degrees", "ROWID");
    $fields[] = new DBField("minor1", DBField::NUM, null, "degrees", "ROWID");
    $fields[] = new DBField("minor2", DBField::NUM, null, "degrees", "ROWID");
    $db->createTable("users", $fields);

    $fields = array();
    $fields[] = new DBField("userID", DBField::NUM, -1, "users", "ROWID");
    $fields[] = new DBField("oldClassID", DBField::NUM, -1, "classes", "ROWID");
    $fields[] = new DBField("newClassID", DBField::NUM, -1, "classes", "ROWID");
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