<?php
    class Autocompleter {
        protected $classesTaken;
        protected $requirements;

        public function __construct(ClassList $classesTaken, ClassList $requirements, Notes $notes) {
            $this->classesTaken = $classesTaken;
            $this->requirements = $requirements;
        }

        public function substitute($user) {
            $this->directSubstitutions($user);
        }

        /**
         * Applies direct substitutions (course numbers or titles equivalent)
         *
         * THIS IS NOT TESTED!
         *
         * @param INTEGER $user ID of the user who has taken classes.
         * @return VOID
         */
        protected function directSubstitutions($user) {
            //Identical classes must be valid substitutes. Seeing as they're identical...
            foreach($this->requirements as $key=>$class) {
                if(isset($this->classesTaken[$key])) {
                    substituteClass($user, $class->getID(), $this->classesTaken[$key]->getID());
                }
            }
/*                foreach($classes as $key=>$class2) {
                    if($class2->isSubstitute) {
                        continue;
                    }
                    foreach($this->classes as $class) {
                        if($class->isComplete()) {
                            continue;
                        }
                        $note = $notes->getNote($class->getNoteID());
                        if(!empty($note) && preg_match("/(\w{4})\s*(\d{4}).*(\w{4})\s*(\d{4})/isS", $note, $matches)) {
                            //explicit course substitution
                            if($class2->getDepartment() == $matches[1] && $class2->getNumber() == $matches[2]) {
                                substituteClass($user, $class->getID(), $class2->getID());
                                $this->completeClass($class, $class2);
                                break;
                            }
                        }
                        if($class->getNumber()) {
                            //title & department matching (they change the middle two number sometimes, seemingly for no good reason...
                            if($class->getDepartment() == $class2->getDepartment() && $class->getTitle() == $class2->getTitle()) {
                                substituteClass($user, $class->getID(), $class2->getID());
                                $this->completeClass($class, $class2);
                                break;
                            }
                        }
                        if($class->getDepartment() == $class2->getDepartment() && $class->getHours() <= $class2->getHours()) {
                            substituteClass($user, $class->getID(), $class2->getID());
                            $this->completeClass($class, $class2);
                            break;
                        }
                    }
                }*/
        }
    }
?>