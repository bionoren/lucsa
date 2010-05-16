<?php
    class ClassList implements ArrayAccess, Countable, Iterator {
        protected $classes = array();
        protected $count = 0;
        protected $innerCount = 0;

        public function __construct($vals=array()) {
            foreach($vals as $key=>$val) {
                $this[$key] = $val;
            }
        }

        public function sort() {
            uasort($this->classes, function(array $first, array $second) { return strcmp($first[0], $second[0]); });
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
            return $this->classes[$index];
        }

        public function offsetSet($index, $value) {
            $this->count++;
            $this->classes[$index][] = $value;
        }

        public function offsetUnset($index) {
            if(isset($this->classes[$index]) && is_array($this->classes[$index])) {
                $this->count-= count($this->classes[$index]);
                unset($this->classes[$index]);
            }
        }
    }
?>