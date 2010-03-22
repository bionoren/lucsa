<?php
    require_once("SQLiteManager.php");

    $db = new SQLiteManager("lucsa.sqlite");
    //catalogs
    $fields = array();
    $fields[] = new DBField("year", DBField::NUM);
    $db->createTable("years", $fields);
    //majors
    $fields = array();
    $fields[] = new DBField("yearID", DBField::NUM, null, "years", "ROWID");
    $fields[] = new DBField("id", DBField::NUM);
    $fields[] = new DBField("name", DBField::STRING);
    $fields[] = new DBField("acronym", DBField::STRING);
    $fields[] = new DBField("type", DBField::NUM); //none, major, minor
    $db->createTable("degrees", $fields);
    //classes
    $fields = array();
    $fields[] = new DBField("degreeID", DBField::NUM, null, "degrees", "ROWID");
    $fields[] = new DBField("department", DBField::NUM);
    $fields[] = new DBField("departmentid", DBField::NUM);
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("id", DBField::STRING);
    $fields[] = new DBField("semester", DBField::NUM);
    $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
    $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
    $fields[] = new DBField("hours", DBField::NUM, 3);
    $fields[] = new DBField("extra", DBField::STRING);
    $db->createTable("classes", $fields);

    $fields = array();
    $fields[] = new DBField("user", DBField::STRING); //hash of username
    $db->createTable("users", $fields);

    //user->majors
    //user->minors
    //classSubstitution->user
?>