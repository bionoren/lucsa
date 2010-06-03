<?php
    class ClassList implements ArrayAccess, Countable, Iterator {
        protected $classes = array();
        protected $count = 0;
        protected $innerCount = 0;

        public function __construct(array $vals=array()) {
            foreach($vals as $val) {
                $this[$val->getID()] = $val;
            }
        }

        public function sort() {
            uasort($this->classes, function(array $first, array $second) { return strcmp($first[0], $second[0]); });
        }

        /**
         * Returns a new ClassList with all the elements in $list1 XOR $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 xor $list2.
         */
        public static function diff(ClassList $list1, ClassList $list2) {
            $ret = new ClassList();
            return $ret->diffHelper($list1, $list2);
        }

        protected function diffHelper(ClassList $list1, ClassList $list2) {
            $this->classes = $list1->classes;
            $this->count = $list1->count();
            foreach($this->classes as $id=>$arr) {
                foreach($arr as $class) {
                    if(isset($list2->classes[$id]) && in_array($class, $list2->classes[$id], true)) {
                        $key = array_search($class, $list2->classes[$id], true);
                        unset($this->classes[$key][$key]);
                        $this->count--;
                    } else {
                        $this->classes[$id][] = $class;
                        $this->count++;
                    }
                }
            }
            return $this;
        }

        /**
         * Returns a new ClassList with all the elements in $list1 AND $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 AND $list2.
         */
        public static function intersect(ClassList $list1, ClassList $list2) {
            $ret = new ClassList();
            return $ret->intersectHelper($list1, $list2);
        }

        protected function intersectHelper(ClassList $list1, ClassList $list2) {
            $this->classes = $list1->classes;
            $this->count = $list1->count();
            foreach($this->classes as $id=>$arr) {
                if(!$list2->offsetExists($id)) {
                    $this->count -= count($arr);
                    unset($this->classes[$id]);
                    continue;
                }
                foreach($arr as $key=>$class) {
                    if(!in_array($class, $list2->classes[$id], true)) {
                        unset($arr[$key]);
                        $this->count--;
                    }
                }
                $this->classes[$id] = array_values($arr);
            }
            return $this;
        }

        /**
         * Returns a new ClassList with all the elements in $list1 OR $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 OR $list2.
         */
        public static function merge(ClassList $list1, ClassList $list2) {
            $ret = new ClassList();
            return $ret->mergeHelper($list1, $list2);
        }

        protected function mergeHelper(ClassList $list1, ClassList $list2) {
            $this->classes = $list1->classes;
            $this->count = $list1->count();
            foreach($list2->classes as $id=>$arr) {
                if(empty($this->classes[$id])) {
                    $this->classes[$id] = $arr;
                    $this->count += count($arr);
                    continue;
                }
                foreach($arr as $class) {
                    if(!in_array($class, $this->classes[$id], true)) {
                        $this[$id] = $class;
                        $this->count++;
                    }
                }
            }
            return $this;
        }

        public function count() {
            return $this->count;
        }

        public function current() {
            $arr = current($this->classes);
            return $arr[$this->innerCount];
        }

        public function key() {
            return $this->current()->getID();
        }

        public function next() {
            if(++$this->innerCount == count(current($this->classes))) {
                $this->innerCount = 0;
                next($this->classes);
            }
        }

        public function rewind() {
            $this->innerCount = 0;
            reset($this->classes);
        }

        public function valid() {
            return current($this->classes) !== false;
        }

        public function offsetExists($index) {
            return !empty($this->classes[$index]);
        }

        public function offsetGet($index) {
            if(isset($this->classes[$index])) {
                return $this->classes[$index][0];
            } else {
                return null;
            }
        }

        public function offsetSet($index, $value) {
            $this->count++;
            $this->classes[$index][] = $value;
        }

        public function offsetUnset($index) {
            if(isset($this->classes[$index])) {
                $this->count--;
                if(count($this->classes[$index]) <= 1) {
                    unset($this->classes[$index]);
                } else {
                    array_shift($this->classes[$index]);
                }
            }
        }

        public function dump($name) {
            dump($name, $this->classes);
        }
    }
?>