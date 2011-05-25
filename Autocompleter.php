<?php
    class Autocompleter {
        protected $classesTaken;
        protected $requirements;
        protected $notes;

        public function __construct(ClassList $classesTaken, ClassList $requirements, Notes $notes) {
            $this->classesTaken = $classesTaken;
            $this->requirements = $requirements;
            $this->notes = $notes;
        }

        public function substitute($user) {
            $this->subFromID($user);
            $this->subFromNotes($user);
            $this->subFromTitle($user);
        }

        /**
         * Applies direct substitutions (course numbers or titles equivalent)
         *
         * THIS IS NOT TESTED!
         *
         * @param INTEGER $user ID of the user who has taken classes.
         * @return VOID
         */
        protected function subFromID($user) {
            //Identical classes must be valid substitutes. Seeing as they're identical...
            foreach($this->requirements as $key=>$class) {
                if(isset($this->classesTaken[$key])) {
                    substituteClass($user, $class->getID(), $this->classesTaken[$key]->getID());
                }
            }
        }

        protected function subFromNotes($user) {
            foreach($this->requirements as $key=>$class) {
                if($class->isComplete()) {
                    continue;
                }
                foreach($this->classesTaken as $class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    $note = $this->notes->getNote($class->getNoteID());
                    if(!empty($note) && preg_match("/(\w{4})\s*(\d{4}).*(\w{4})\s*(\d{4})/isS", $note, $matches)) {
                        //explicit course substitution
                        if($class2->getDepartment() == $matches[1] && $class2->getNumber() == $matches[2]) {
                            substituteClass($user, $class->getID(), $class2->getID());
                            break;
                        }
                    }
                }
            }
        }

        protected function subFromTitle($user) {
            foreach($this->requirements as $key=>$class) {
                if($class->isComplete()) {
                    continue;
                }
                foreach($this->classesTaken as $class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    if($class->getNumber()) {
                        //title & department matching (they change the middle two number sometimes, seemingly for no good reason...
                        if($class->getDepartment() == $class2->getDepartment() && $class->getTitle() == $class2->getTitle()) {
                            substituteClass($user, $class->getID(), $class2->getID());
                            break;
                        }
                    }
                }
            }
        }

        /*
        if($class->getDepartment() == $class2->getDepartment() && $class->getHours() <= $class2->getHours()) {
            substituteClass($user, $class->getID(), $class2->getID());
            $this->completeClass($class, $class2);
            break;
        }*/
    }
?>