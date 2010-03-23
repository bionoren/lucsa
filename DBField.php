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

    class DBField {
        const STRING = "TEXT";
        const NUM = "INTEGER";

        protected $name;
        protected $type;
        protected $default;
        protected $keyTable;
        protected $keyField;
        protected $unique;

        public function __construct($name, $type, $default=null, $keyTable=null, $keyField=null) {
            $this->name = $name;
            $this->type = $type;
            $this->default = $default;
            $this->keyTable = $keyTable;
            $this->keyField = $keyField;
            $this->unique = false;
        }

        public function createInTable() {
            $ret = $this->name." ".$this->type;
            if($this->default != null) {
                $ret .= " DEFAULT ".$this->default;
            }
            if($this->keyTable != null) {
                $ret .= " REFERENCES ".$this->keyTable."(".$this->keyField.")";
                $ret .= " ON UPDATE CASCADE ON DELETE CASCADE";
            }
            return $ret;
        }

        public function getName() {
            return $this->name;
        }

        public function setUnique() {
            $this->unique = true;
        }

        public function isUnique() {
            return $this->unique;
        }
    }
?>