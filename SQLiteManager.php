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

    require_once("DBField.php");

    class SQLiteManager {
        protected static $instance = null;

        private $journal = "MEMORY";
        private $sync = "OFF";

        protected $db;

        protected function __construct($db) {
            try {
                $this->db = new SQLite3($db, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            } catch(Exception $e) {
                die($e);
            }
            $this->query("PRAGMA synchronous = ".$this->sync);
            $this->query("PRAGMA journal_mode = ".$this->journal);
            $this->query("PRAGMA foreign_keys = ON");
        }

        public function changed() {
            return $this->db->changes() != 0;
        }

        public function close() {
            $this->db->close();
        }

        public function createTable($name, array $fields) {
            //every table gets a primary key alias to keep foreign key constraints happy
            $keyField = new DBField("ID", DBField::NUM);
            $keyField->setPrimary();
            array_unshift($fields, $keyField);
            //we're smacking all the tables, so force them to go away
            $this->query("PRAGMA foreign_keys = OFF");
            $this->query("DROP TABLE IF EXISTS ".$name);
            $this->query("PRAGMA foreign_keys = ON");
            $sql = "CREATE TABLE IF NOT EXISTS ".$name." (";
            foreach($fields as $field) {
                $sql .= $field->createInTable().",";
            }
            $sql = substr($sql, 0, -1).")";
            $this->query($sql);
            foreach($fields as $field) {
                if($field->isUnique()) {
                    $sql = "CREATE UNIQUE INDEX IF NOT EXISTS ".$field->getName()." ON ".$name." (".$field->getName().");";
                    $this->query($sql);
                }
                if($field->isIndexed()) {
                    $sql = "CREATE INDEX IF NOT EXISTS ".$field->getName()." ON ".$name." (".$field->getName().");";
                    $this->query($sql);
                }
            }
        }

        public function createUniqueConstraint($name, array $fields) {
            $sql = "CREATE UNIQUE INDEX IF NOT EXISTS u".current($fields)->getName()." ON ".$name." (";
            $tmp = "";
            foreach($fields as $field) {
                $tmp .= $field->getName().",";
            }
            $sql .= substr($tmp, 0, -1).")";
            return $this->query($sql);
        }

        public static function getInstance($debug=false) {
            if(SQLiteManager::$instance == null) {
                $name = "lucsa";
                if($debug) {
                    $name .= "-debug";
                }
                SQLiteManager::$instance = new SQLiteManager($name.".sqlite");
            }
            return SQLiteManager::$instance;
        }

        public function getLastInsertID() {
            return $this->db->lastInsertRowID();
        }

        public function insert($table, array $fields, $ignore=false) {
            $sql = "INSERT ";
            if($ignore) {
                $sql .= "OR IGNORE ";
            }
            $sql .= "INTO ".$table." ";
            $colStr = "";
            $valStr = "";
            foreach($fields as $key=>$val) {
                $colStr .= "'".$key."',";
                if((string)intval($val) == $val) {
                    $valStr .= $val.",";
                } else {
                    $valStr .= "'".SQLite3::escapeString($val)."',";
                }
            }
            $sql .= "(".substr($colStr, 0, -1).") VALUES (".substr($valStr, 0, -1).")";
            return $this->query($sql);
        }

        public function query($sql) {
            $ret = $this->db->query($sql);
            if($ret === false) {
                print "sql = $sql<br>";
                print "<span style='color:red'>".$this->db->lastErrorMsg()."</span><br>";
                die();
            }
            return $ret;
        }

        public function select($table, array $whereFields=null, array $fields=null) {
            $sql = "SELECT ";
            if(!empty($fields)) {
                foreach($fields as $val) {
                    $sql .= $val.",";
                }
                $sql = substr($sql, 0, -1);
            } else {
                $sql .= "*";
            }
            $sql .= " FROM ".$table;
            if(!empty($whereFields)) {
                $sql .= " WHERE ";
                foreach($whereFields as $key=>$val) {
                    $sql .= $key."='".SQLite3::escapeString($val)."' AND ";
                }
                $sql = substr($sql, 0, -5);
            }
            return $this->query($sql);
        }

        public function update($table, array $fields, array $whereFields) {
            $sql = "UPDATE ".$table." SET ";
            foreach($fields as $key=>$val) {
                $sql .= $key."='".$val."',";
            }
            $sql = substr($sql, 0, -1)." WHERE ";
            foreach($whereFields as $key=>$val) {
                $sql .= $key."='".SQLite3::escapeString($val)."' AND ";
            }
            $sql = substr($sql, 0, -5);
            return $this->query($sql);
        }

        function __destruct() {
            $this->close();
        }
    }
?>