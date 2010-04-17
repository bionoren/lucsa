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

    class Course {
        protected static $fetchSQL = "SELECT classes.*,
                       departments.department, departments.linkid AS deptlinkid,
                       years.year
                       FROM classes
                       JOIN departments ON classes.departmentID = departments.ID
                       JOIN years ON classes.yearID=years.ID ";

        protected $completeClass = null;
        protected $department;
        protected $departmentlinkid;
        protected $hours;
        protected $ID;
        protected $linkid;
        protected $notes;
        protected $number;
        protected $title;
        protected $year;

        public function __construct(array $row) {
            $this->ID = intval($row["ID"]);
            $this->year = $row["year"];
            $this->department = $row["department"];
            $this->departmentlinkid = $row["deptlinkid"];
            $this->number = $row["number"];
            $this->title = $row["title"];
            $this->linkid = $row["linkid"];
            $this->hours = $row["hours"];
            $this->offered = $row["offered"];
            $this->years = $row["years"];
        }

        public function display($year, array &$notes) {
            $url = 'http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$this->getYear()."&";

            print '<tr class="course';
            if($this->isComplete()) {
                print ' strike';
            }
            print '">';
                print '<td ';
                print 'style="width:0px;">';
                    print '<a href="'.$url.'school='.$this->getDepartmentLink().'&cmd=courselist">';
                        print $this->getDepartment();
                    print '</a>';
                print '</td>';
                print '<td style="width:0px;">';
                    print $this->getNumber();
                print '</td>';
                print '<td>';
                    print '<a href="'.$url.'course='.$this->getLink().'">';
                        print $this->getTitle();
                    print '</a>';
                    if(!$this->getNumber()) {
                        print '<span class="note">';
                            print " (".$this->getHours()." hour";
                            if($this->getHours() != 1) {
                                print "s";
                            }
                            print ")";
                        print '</span>';
                    }
                    if($this->getOffered() < 3 || $this->getYears() < 3) {
                        print '<span class="note">';
                            print " (";
                            if($this->getOffered() < 3) {
                                print ($this->getOffered() == 1)?"Spring":"Fall";
                                if($this->getYears() < 3) {
                                    print ", ";
                                }
                            }
                            if($this->getYears() < 3) {
                                print ($this->getYears() == 1)?"Odd":"Even";
                                print " years";
                            }
                            print " only)";
                        print '</span>';
                    }
                    if(!empty($this->notes)) {
                        $key = array_search($this->getNotes(), $notes);
                        if($key === false) {
                            $key = count($notes);
                            $notes[] = $this->getNotes();
                        }
                        print '<span class="footnote">';
                            print " (".($key+1).")";
                        print '</span>';
                    }
                    if($this->isComplete()) {
                        print '<br>'.$this->getCompleteClass();
                    }
                print '</td>';
            print '</tr>';
        }

        public function equals(Course $class2) {
            return $this->getID() === $class2->getID();
        }

        protected function getCompleteClass() {
            return $this->completeClass;
        }

        public function getDepartment() {
            return $this->department;
        }

        public function getDepartmentLink() {
            return $this->departmentlinkid;
        }

        public static function getFromID($id) {
            $db = SQLiteManager::getInstance();
            $sql = Course::$fetchSQL."WHERE classes.ID=".$id;
            $result = $db->query($sql);
            return new Course($result->fetchArray(SQLITE3_ASSOC));
        }

        public static function getFromDepartmentNumber($year, $dept, $num, $title="") {
            $db = SQLiteManager::getInstance();
            //try to get the class from our year if we can
            $sql = Course::$fetchSQL."WHERE departments.department='".$dept."' AND ".$num." BETWEEN classes.number AND classes.endNumber AND classes.yearID%s".$year;
            $result = $db->query(sprintf($sql, "="));
            $ret = $result->fetchArray(SQLITE3_ASSOC);
            if(!$ret) {
                //otherwise, it must be from a more recent year
                $result = $db->query(sprintf($sql, "<="));
                $ret = $result->fetchArray(SQLITE3_ASSOC);
            }
            if(!empty($title)) {
                //if the user provided a title, use it. And the number too, just to be safe.
                //(because the number could have been in a range somewhere on the stored class)
                $ret["title"] = $title;
                $ret["number"] = $num;
            }
            return new Course($ret);
        }

        public function getHours() {
            return $this->hours;
        }

        public function getID() {
            return $this->ID;
        }

        public function getLink() {
            return $this->linkid;
        }

        public function getNotes() {
            return $this->notes;
        }

        public function getNumber() {
            return $this->number;
        }

        public function getOffered() {
            return $this->offered;
        }

        public function getTitle() {
            return $this->title;
        }

        public function getYear() {
            return $this->year;
        }

        public function getYears() {
            return $this->years;
        }

        public function isComplete() {
            return $this->completeClass !== null;
        }

        public function setComplete(Course $class) {
            $this->completeClass = $class;
            if(!$class->isComplete()) {
                $class->setComplete($this);
            }
        }

        public function setNotes($note) {
            $this->notes = $note;
        }

        public function __toString() {
            return $this->getDepartment().$this->getNumber()." - ".$this->getTitle();
        }
    }
?>