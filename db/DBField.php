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

    /**
     * Manages creating and modifying a SQLite table field.
     *
     * @author Bion Oren
     */
    class DBField {
        /** STRING Constant for a text data type. */
        const STRING = "TEXT";
        /** STRING Constant for an integer data type. */
        const NUM = "INTEGER";

        /** STRING The name of this field. */
        protected $name;
        /** STRING One of the field type constants in this class. */
        protected $type;
        /** MIXED The default value for this field. */
        protected $default;
        /** STRING If this field is a foreign key, the name of the table it is linked back to. */
        protected $keyTable;
        /** STRING If this field is a foreign key, the name of the field in $keyTable it is linked to. */
        protected $keyField;
        /** BOOLEAN True if this field is unique. */
        protected $unique;
        /** BOOLEAN True if this field should be indexed. */
        protected $indexed;
        /** BOOLEAN True if this is the primary key. */
        protected $primary;

        /**
         * Creates a new field manager.
         *
         * @param STRING $name The name of this database field.
         * @param INTEGER $type One of the class' type constants.
         * @param MIXED $default Default value for the field.
         * @param STRING $keyTable Optional name of the table this field references as a foreign key.
         * @param STRING $keyField Optional name of the field this field references as a foreign key.
         */
        public function __construct($name, $type, $default=null, $keyTable=null, $keyField=null) {
            $this->name = $name;
            $this->type = $type;
            $this->default = $default;
            $this->keyTable = $keyTable;
            $this->keyField = $keyField;
            $this->unique = false;
            $this->indexed = false;
            if($keyTable != null) {
                $this->setIndexed();
            }
        }

        /**
         * Creates the SQL necessary to create this field.
         *
         * @return STRING SQL statement.
         */
        public function createInTable() {
            $ret = $this->name." ".$this->type;
            if($this->default != null) {
                $ret .= " DEFAULT ".$this->default;
            }
            if($this->keyTable != null) {
                $ret .= " REFERENCES ".$this->keyTable;
                if($this->keyField != null) {
                    $ret .= "(".$this->keyField.")";
                }
                $ret .= " ON UPDATE CASCADE ON DELETE CASCADE";
            }
            if($this->isPrimary()) {
                $ret .= " PRIMARY KEY";
            }
            return $ret;
        }

        /**
         * Returns the name of this field.
         *
         * @return STRING field name.
         */
        public function getName() {
            return $this->name;
        }

        /**
         * Getter for indexed
         *
         * @return BOOLEAN
         * @see indexed
         */
        public function isIndexed() {
            return $this->indexed;
        }

        /**
         * Getter for primary
         *
         * @return BOOLEAN
         * @see primary
         */
        public function isPrimary() {
            return $this->primary;
        }

        /**
         * Getter for unique
         *
         * @return BOOLEAN
         * @see unique
         */
        public function isUnique() {
            return $this->unique;
        }

        /**
         * Marks this field to be indexed if it is not already unique.
         *
         * @return VOID
         * @see setUnique
         */
        public function setIndexed() {
            if(!$this->unique) {
                $this->indexed = true;
            }
        }

        /**
         * Makes this field primary
         *
         * @return VOID
         * @see primary
         */
        public function setPrimary() {
            $this->primary = true;
        }

        /**
         * Marks this field to be unique and not indexed (since unique is an index).
         *
         * @return VOID
         */
        public function setUnique() {
            $this->unique = true;
            $this->indexed = false;
        }
    }
?>