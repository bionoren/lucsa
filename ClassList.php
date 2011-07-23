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

    /*
     * You know, the whole reason this class exists is because there are classes that, from
     * the API's perspective, are the same. But, from the user's perspective, they're
     * completely different. You might be able to encapsulate the differences in arrays
     * inside the classes themselves (kind of like reference counters). If this becomes a
     * performance bottleneck...
     */

    require_once($path."Object.php");

    /**
     * Defines an associative array of classes that may contain duplicates.
     *
     * @author Bion Oren
     */
    class ClassList extends Object implements ArrayAccess, Countable, Iterator {
        /** ARRAY Internal class structure of the form $classes[KEY] = array(item, item, ...). */
        protected $classes = array();
        /** INTEGER Number of elements in the array. */
        protected $count = 0;
        /** INTEGER index into the current element of the current element in $classes. */
        protected $innerCount = 0;

        /**
         * Constructs a new object.
         *
         * @param ARRAY $vals Simple array of items to prepopulate this list with.
         */
        public function __construct(array $vals=array()) {
            foreach($vals as $val) {
                $this[$val->ID] = $val;
            }
        }

        /**
         * Returns the total number of elements in the list.
         *
         * @return INTEGER Number of elements.
         * @see count
         */
        public function count() {
            return $this->count;
        }

        /**
         * Returns the current element in the array.
         *
         * @return MIXED Current item in the list.
         */
        public function current() {
            $arr = current($this->classes);
            return $arr[$this->innerCount];
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

        /**
         * Returns this ClassList with all the elements in $list1 XOR $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 xor $list2.
         */
        protected function diffHelper(ClassList $list1, ClassList $list2) {
            foreach($list1->classes as $id=>$arr) {
                foreach($arr as $class) {
                    if(!isset($list2->classes[$id]) || !in_array($class, $list2->classes[$id], true)) {
                        $this[$id] = $class;
                    }
                }
            }
            foreach($list2->classes as $id=>$arr) {
                foreach($arr as $class) {
                    if(!isset($list1->classes[$id]) || !in_array($class, $list1->classes[$id], true)) {
                        $this[$id] = $class;
                    }
                }
            }
            return $this;
        }

        /**
         * Debug function to print the internal contents of this list.
         *
         * @param STRING $name Name to use to identify the output.
         * @return VOID
         */
        public function dump($name) {
            dump($name, $this->classes);
        }

        /**
         * Filters the class list based on a user defined function.
         *
         * @param FUNCTION $function Returns true if the element should be returned.
         * @return ClassList New filtered ClassList instance.
         */
        public function filter($function) {
            $ret = new ClassList();
            return $ret->filterHelper($this, $function);
        }

        /**
         * Performs the actual filter operation.
         *
         * @param ClassList $classes List of classes to filter.
         * @param FUNCTION $function Returns true if the element should be returned.
         * @return ClassList Filtered ClassList.
         * @see filter
         */
        protected function filterHelper(ClassList $classes, $function) {
            foreach($classes as $class) {
                if($function($class)) {
                    $this[$class->ID] = $class;
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

        /**
         * Returns this ClassList with all the elements in $list1 AND $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 AND $list2.
         */
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
                    foreach($list2->classes[$id] as $class2) {
                        if($class->equals($class2)) {
                            break 2;
                        }
                    }
                    unset($arr[$key]);
                    $this->count--;
                }
                $this->classes[$id] = array_values($arr);
            }
            return $this;
        }

        /**
         * Returns the key for the current element in the array.
         * @return STRING Item key.
         */
        public function key() {
            return $this->current()->ID;
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

        /**
         * Returns this ClassList with all the elements in $list1 OR $list2.
         *
         * @param ClassList $list1 One list.
         * @param ClassList $list2 Another list.
         * @return ClassList $list1 OR $list2.
         */
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
                    foreach($this->classes[$id] as $class2) {
                        if($class->equals($class2)) {
                            break 2;
                        }
                    }
                    $this[$id] = $class;
                    $this->count++;
                }
            }
            return $this;
        }

        /**
         * Moves to the next element in the list.
         *
         * @return VOID
         */
        public function next() {
            if(++$this->innerCount == count(current($this->classes))) {
                $this->innerCount = 0;
                next($this->classes);
            }
        }

        /**
         * Checks to see if $index is a valid key.
         *
         * @param STRING $index Key to test.
         * @return BOOLEAN True if the index $key exists.
         */
        public function offsetExists($index) {
            return !empty($this->classes[$index]);
        }

        /**
         * Returns the first element at the given offset.
         *
         * @param STRING $index Key to use.
         * @return MIXED The first item at the given key, or null if the index doesn't exist.
         */
        public function offsetGet($index) {
            if(isset($this->classes[$index])) {
                return $this->classes[$index][0];
            } else {
                return null;
            }
        }

        /**
         * Adds a value to the list.
         *
         * @param STRING $index Key to insert with.
         * @param MIXED $value Item to insert at $index.
         * @return VOID
         */
        public function offsetSet($index, $value) {
            $this->count++;
            $this->classes[$index][] = $value;
        }

        /**
         * Removes an element at the specified index.
         *
         * @param STRING $index Index to remove an element from.
         * @return VOID
         */
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

        /**
         * Resets the internal array pointers to the beginning of the list.
         *
         * @return VOID
         */
        public function rewind() {
            $this->innerCount = 0;
            reset($this->classes);
        }

        /**
         * Sorts the internal class list.
         *
         * @return ARRAY Sorted internal class list.
         */
        public function sort() {
            uasort($this->classes, function(array $first, array $second) { return strcmp($first[0], $second[0]); });
        }

        /**
         * Returns true if we haven't hit the end of the list yet.
         *
         * @return BOOLEAN True if there are more elements.
         */
        public function valid() {
            return current($this->classes) !== false;
        }

        public function __clone() {
            $temp = $this->classes;
            $this->classes = array();
            $this->count = 0;
            $this->innerCount = 0;
            foreach($temp as $key=>$arr) {
                foreach($arr as $class) {
                    $this[$key] = clone $class;
                }
            }
        }

        public function __toString() {
            return "ClassList(".$this->count().")";
        }
    }
?>