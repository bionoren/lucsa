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

    if(!isset($path)) {
        $path = "../";
    }
    require_once($path."db/SQLiteManager.php");
    require_once($path."functions.php");

    $db = SQLiteManager::getInstance();

    //catalogs
    $fields = array();
    $fields[] = $field = new DBField("year", DBField::NUM);
    $field->setUnique();
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
    $db->createUniqueConstraint("degrees", array($fields[0], $fields[3], $fields[4]));

    //departments
    $fields = array();
    $fields[] = $field = new DBField("department", DBField::STRING);
    $field->setUnique();
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("linkid", DBField::NUM);
    $db->createTable("departments", $fields);

    //classes
    $fields = array();
    $fields[] = new DBField("departmentID", DBField::NUM, null, "departments", "ID");
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("endNumber", DBField::NUM); //only used for ranges, like special topics classes
    $fields[] = new DBField("title", DBField::STRING);
    $fields[] = new DBField("linkid", DBField::NUM);
    $fields[] = new DBField("offered", DBField::NUM, 3); //never, spring, fall, both
    $fields[] = new DBField("years", DBField::NUM, 3); //never, odd, even, both
    $fields[] = new DBField("hours", DBField::NUM, 3);
    $db->createTable("classes", $fields);
    //department and number
    $db->createUniqueConstraint("classes", array_slice($fields, 0, 3));
    $db->query("CREATE INDEX IF NOT EXISTS deptnum ON classes (departmentID, number)");

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
    $db->query("CREATE INDEX IF NOT EXISTS degreeSemester ON degreeCourseMap (degreeID, semester)");

    //users
    $fields = array();
    $fields[] = $field = new DBField("user", DBField::STRING); //hash of username
    $field->setUnique();
    $db->createTable("users", $fields);

    //course subsitutions for individual users
    $fields = array();
    $fields[] = $field = new DBField("userID", DBField::NUM, -1, "users");
    $field->setIndexed();
    $fields[] = new DBField("oldClassID", DBField::NUM, -1, "classes");
    $fields[] = new DBField("newClassID", DBField::NUM, -1, "classes");
    $db->createTable("userClassMap", $fields);

    $fields = array();
    $fields[] = $field = new DBField("userID", DBField::NUM, -1, "users");
    $field->setIndexed();
    $fields[] = new DBField("oldDegreeID", DBField::NUM, -1, "degrees");
    $fields[] = new DBField("newDegreeID", DBField::NUM, -1, "degrees");
    $fields[] = new DBField("oldClassID", DBField::NUM, -1, "classes");
    $fields[] = new DBField("newClassID", DBField::NUM, -1, "classes");
    $db->createTable("userDegreeClassMap", $fields);

    //tabs
    $fields = array();
    $fields[] = $field = new DBField("userID", DBField::NUM, -1, "users");
    $field->setIndexed();
    $fields[] = new DBField("number", DBField::NUM);
    $fields[] = new DBField("degreeList", DBField::STRING);
    $db->createTable("userTabs", $fields);

    //initialize the years
    $data = getCache("http://www.letu.edu/academics/catalog/");
    $matches = array();
    preg_match_all("/\>(\d{4})-\d{4}\</is", $data, $matches, PREG_PATTERN_ORDER);
    $years = $matches[1];
    //put the years in oldest to newest
    foreach(array_reverse($years) as $year) {
        $db->insert("years", array("year"=>$year));
    }
?>