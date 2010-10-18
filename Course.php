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
        const fetchSQL = "SELECT classes.*,
                       departments.department, departments.linkid AS deptlinkid
                       FROM classes
                       JOIN departments ON classes.departmentID = departments.ID ";

        protected $completeClass = null;
        protected $department;
        protected $departmentlinkid;
        protected $hours;
        protected $ID;
        protected $linkid;
        protected $noteID = null;
        protected $number;
        protected $title;
        public $isSubstitute = false;

        protected function __construct(array $row) {
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

        public function display($year=null, $extended=true) {
			$url = 'http://www.letu.edu/academics/catalog/index.htm?cat_type=tu&cat_year='.$year."&";
			if($extended) {
				print '<span id="classDisplay'.$this->getUID().'" class="classOverlay';
				print ($this->isComplete())?' strike':' nostrike';
				print '">';
                print '<script type="text/javascript">
                    Droppables.add("classDisplay'.$this->getUID().'", {overlap:"vertical", onDrop: function(dragged, dropped, event) {
                        alert(dropped.id);
                    } });
                </script>';
			}
				if($this->isComplete()) {
					print '<div class="overlay">';
						print 'Completed By:<br/>';
						$_GET["substitute"] = true;
						$_GET["orig"] = $this->ID;
						$_GET["sub"] = null;
						print '<a href="'.getQS().'" class="ximage">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>';
						$this->getCompleteClass()->display($year, false);
					print '</div>';
				}
				print '<span class="classDepartment">';
					if($year) {
						print '<a href="'.$url.'school='.$this->getDepartmentLink().'&cmd=courselist">';
					}
					print $this->getDepartment();
					if($year) {
						print '</a>';
					}
				print '</span>';
				print '<span class="classNumber">';
					print "| ".$this->getNumber();
				print '</span>';
				print '<span class="classTitle">';
					print "| ";
					if($year) {
						print '<a href="'.$url.'course='.$this->getLink().'">';
					}
						print $this->getTitle();
					if($year) {
						print '</a>';
					}
					if(!$this->getNumber()) {
						print '<span class="note">';
							print " (".$this->getHours()." hour";
							if($this->getHours() != 1) {
								print "s";
							}
							print ")";
						print '</span>';
					}
					if($extended) {
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
						if(!empty($this->noteID)) {
							print '<span class="footnote">';
								print " (".$this->noteID.")";
							print '</span>';
						}
					}
				print '</span>';
			if($extended) {
				print '</span>';
			}
        }

        public function equals(Course $class) {
            return $class->getID() == $this->getID() && $class->getTitle() == $this->getTitle();
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
            $sql = Course::fetchSQL."WHERE classes.ID=".$id;
            $result = $db->query($sql);
            return new Course($result->fetchArray(SQLITE3_ASSOC));
        }

        public static function getFromDepartmentNumber($dept, $num, $title="") {
            $db = SQLiteManager::getInstance();
            //try to get the class from our year if we can
            $sql = Course::fetchSQL."WHERE departments.department='".$dept."' AND ".$num." BETWEEN classes.number AND classes.endNumber";
            $result = $db->query($sql);
            $ret = $result->fetchArray(SQLITE3_ASSOC);
            if($ret === false) {
                return null;
            }
            if(!empty($title)) {
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

        public function getNoteID() {
            return $this->noteID;
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

		public function getUID() {
			return $this->getDepartment().$this->getNumber();
		}

        public function getYears() {
            return $this->years;
        }

        public function isComplete() {
            return $this->completeClass !== null;
        }

        public function setComplete(Course $class) {
            $this->completeClass = $class;
            $class->isSubstitute = true;
        }

		public function setAvailableForCompletion() {
			print '<script type="text/javascript">';
				print 'new Draggable("'.$this->getUID().'", {revert:true, scroll:window, ghosting:true})';
			print '</script>';
		}

        public function setNoteID($id) {
            $this->noteID = $id;
        }

        public function __toString() {
            return $this->getDepartment().$this->getNumber()." - ".$this->getTitle();
        }
    }
?>