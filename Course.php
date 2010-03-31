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
        protected $ID;
        protected $department;
        protected $departmentlinkid;
        protected $number;
        protected $title;
        protected $linkid;
        protected $hours;
        protected $notes;
        protected $complete = false;
        protected $completeClass = null;

        public function __construct(array $row) {
            $this->ID = intval($row["ID"]);
            $this->department = $row["department"];
            $this->departmentlinkid = $row["deptlinkid"];
            $this->number = $row["number"];
            $this->title = $row["title"];
            $this->linkid = $row["linkid"];
            $this->hours = $row["hours"];
            $this->offered = $row["offered"];
            $this->years = $row["years"];
        }

        static function getFromID(SQLiteManager $db, $id) {
            $sql = "SELECT classes.*,
                       departments.department, departments.linkid AS deptlinkid
                       FROM classes
                       JOIN departments ON classes.departmentID = departments.ID
                       WHERE classes.ID=".$id;
            $result = $db->query($sql);
            return new Course($result->fetchArray(SQLITE3_ASSOC));
        }

        public function getID() {
            return $this->ID;
        }

        public function getDepartment() {
            return $this->department;
        }

        public function getDepartmentLink() {
            return $this->departmentlinkid;
        }

        public function getNumber() {
            return $this->number;
        }

        public function getTitle() {
            return $this->title;
        }

        public function getLink() {
            return $this->linkid;
        }

        public function getHours() {
            return $this->hours;
        }

        public function setNotes($note) {
            $this->notes = $note;
        }

        public function getNotes() {
            return $this->notes;
        }

        public function getOffered() {
            return $this->offered;
        }

        public function getYears() {
            return $this->years;
        }

        public function isComplete() {
            return $this->complete;
        }

        protected function getCompleteClass() {
            return $this->completeClass;
        }

        public function equals(Course $class2) {
            return $this->getID() === $class2->getID();
        }

        public function display($year, array &$notes) {
            print '<tr class="course">';
                print '<td ';
                if($this->isComplete()) {
                    print 'class="strike" ';
                }
                print 'style="width:0px;">';
                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$year.'&school='.$this->getDepartmentLink().'&cmd=courselist">';
                        print $this->getDepartment();
                    print '</a>';
                print '</td>';
                print '<td ';
                if($this->isComplete()) {
                    print 'class="strike" ';
                }
                print 'style="width:0px;">';
                    print $this->getNumber();
                print '</td>';
                print '<td';
                if($this->isComplete()) {
                    print ' class="strike"';
                }
                print '>';
                    print '<a href="http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$year.'&course='.$this->getLink().'">';
                        print $this->getTitle();
                    print '</a>';
                    if(empty($this->number)) {
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
                                if($this->getOffered() == 1) {
                                    print "Spring";
                                } else {
                                    print "Fall";
                                }
                                if($this->getYears() < 3) {
                                    print ",";
                                }
                                print " ";
                            }
                            if($this->getYears() < 3) {
                                if($this->getYears() == 1) {
                                    print "Odd years";
                                } else {
                                    print "Even years";
                                }
                            }
                            print " only";
                            print ")";
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
                    if($this->getCompleteClass() != null) {
                        print '<br>'.$this->getCompleteClass()->getTitle();
                    }
                print '</td>';
            print '</tr>';
        }

        public function __toString() {
            return $this->getDepartment().$this->getNumber()." - ".$this->getTitle();
        }
    }
?>