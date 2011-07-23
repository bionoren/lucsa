<?php
    require_once($path."Object.php");

    class Autocompleter extends Object {
        protected $classesTaken;
        protected $requirements;
        protected $notes;

        public function __construct(ClassList $classesTaken, ClassList $requirements, Notes $notes) {
            $this->classesTaken = $classesTaken;
            $this->requirements = $requirements;
            $this->notes = $notes;
        }

        public function substitute() {
            $this->subFromID();
            $this->subFromNotes();
            $this->subFromTitle();
        }

        /**
         * Applies direct substitutions (course numbers or titles equivalent)
         *
         * @return VOID
         */
        protected function subFromID() {
            //Identical classes must be valid substitutes. Seeing as they're identical...
            foreach($this->requirements as $key=>$class) {
                if(isset($this->classesTaken[$key])) {
                    substituteClass($class->ID, $this->classesTaken[$key]->ID);
                }
            }
        }

        protected function subFromNotes() {
            foreach($this->requirements as $key=>$class) {
                if($class->isComplete()) {
                    continue;
                }
                foreach($this->classesTaken as $class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    $note = $this->notes->getNote($class->noteID);
                    if(!empty($note) && preg_match("/(\w{4})\s*(\d{4}).*(\w{4})\s*(\d{4})/isS", $note, $matches)) {
                        //explicit course substitution
                        if($class2->department == $matches[1] && $class2->number == $matches[2]) {
                            substituteClass($class->ID, $class2->ID);
                            break;
                        }
                    }
                }
            }
        }

        protected function subFromTitle() {
            foreach($this->requirements as $key=>$class) {
                if($class->isComplete()) {
                    continue;
                }
                foreach($this->classesTaken as $class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    if($class->number) {
                        //title & department matching (they change the middle two number sometimes, seemingly for no good reason...
                        if($class->department == $class2->department && $class->title == $class2->title) {
                            substituteClass($class->ID, $class2->ID);
                            break;
                        }
                    }
                }
            }
        }

        /*
        if($class->department == $class2->department && $class->hours <= $class2->hours) {
            substituteClass($user, $class->ID, $class2->ID);
            $this->completeClass($class, $class2);
            break;
        }*/
    }
?>