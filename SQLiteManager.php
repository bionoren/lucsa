<?php
    /*
	 *	Copyright 2009 Bion Oren
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
        protected $db;

        public function __construct($db) {
            try {
                $this->db = new SQLite3($db, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            } catch(Exception $e) {
                die($e);
            }
        }

        public function createTable($name, array $fields) {
            $this->query("DROP TABLE IF EXISTS ".$name);
            $sql = "CREATE TABLE IF NOT EXISTS ".$name." (";
            foreach($fields as $field) {
                $sql .= $field->createInTable().",";
            }
            $this->query(substr($sql, 0, -1).")");
        }

        public function query($sql) {
            $ret = $this->db->query($sql);
            if($ret === false) {
                print "sql = $sql<br>";
                print "<span style='color:red'>".$this->db->lastErrorMsg()."</span><br>";
            }
            return $ret;
        }

        public function insert($table, array $fields) {
            $sql = "INSERT INTO ".$table." ";
            $colStr = "";
            $valStr = "";
            foreach($fields as $key=>$val) {
                $colStr .= $key.",";
                $valStr .= "'".SQLite3::escapeString($val)."',";
            }
            $sql .= "(".substr($colStr, 0, -1).") VALUES (".substr($valStr, 0, -1).")";
            $this->query($sql);
        }

        public function update($table, array $fields, array $whereFields) {
            $sql = "UPDATE ".$table." SET ";
            foreach($fields as $key=>$val) {
                $sql .= $key."='".$val."',";
            }
            $sql = substr($sql, 0, -1)." WHERE ";
            foreach($whereFields as $key=>$val) {
                $sql .= $key."='".SQLite3::escapeString($val)."' AND";
            }
            $sql = substr($sql, 0, -4);
            $this->query($sql);
        }

        public function getLastInsertID() {
            return $this->db->lastInsertRowID();
        }
    }
?>