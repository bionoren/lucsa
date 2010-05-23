<?php
    //$obj is a reference to the object being used to test the current function

    //UNIT TESTS
    //Test Harness
    function unitTest($func, $test) {
        $status = true;
        print 'Testing '.$test["title"].': <font color="';
        ob_start();
        $ret = call_user_func_array($func, $test["args"]);
        if($ret == $test["r"]) {
            if(ob_get_contents() == $test["p"]) {
                ob_end_clean();
                print 'green">Passed</font>';
            } else {
                $tmp = ob_get_contents();
                ob_end_clean();
                print 'red">Failed</font> - '.var_export($tmp, true)." != ".var_export($test["p"], true);
                $status = false;
            }
        } else {
            ob_end_clean();
            print 'red">Failed</font> - '.var_export($ret, true)." != ".var_export($test["r"], true);
            print "<br>".serialize($ret)."<br>";
            dump("ret", $ret->fetchArray(SQLITE3_ASSOC));
            die();
            $status = false;
        }
        print "<br>";
        return $status;
    }

    function passFail($status, $title) {
        if(is_array($title)) {
            $title = $title[1];
        }
        print $title.' Tests: <font color="';
        if($status) {
            print 'green">Passed</font>';
        } else {
            print 'red">Failed</font>';
        }
        print "<br><br>";
    }

    function testFile($filename) {
        print ">Testing ".$filename."php<br>";
        require_once($filename."php");
        $filePass = true;
        $numPassed = 0;
        $numFailed = 0;
        $lineNum = 0;
        $evalCode = false;
        foreach(file($filename."utd", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $lineNum++;
            $line = trim($line);
            if(substr($line, 0, 2) == "//") {
                continue;
            }
            if(strtolower(substr($line, 0, 9)) == "function ") {
                if(isset($pass)) {
                    print "-----------------------------<br>";
                    passFail($pass, $functionName);
                }
                $functionName = substr($line, 9);
                if(!function_exists($functionName)) {
                    die("<font color='red'>Error: Line ".$lineNum." - Unknown function '".$functionName."'</font>");
                }
                print '>Testing '.$line.'<br>';
                $pass = true;
                continue;
            }
            if(strtolower(substr($line, 0, 7)) == "method ") {
                if(!is_object($obj)) {
                    die("<font color='red'>Syntax Error: Line ".$lineNum." - Must define an object to test on using the directive 'object [code_to_instantiate_object]'</font>");
                }
                if(isset($pass)) {
                    print "-----------------------------<br>";
                    passFail($pass, $functionName);
                }
                $functionName = array($obj, substr($line, 7));
                if(!method_exists($functionName[0], $functionName[1])) {
                    die("<font color='red'>Error: Line ".$lineNum." - There is no method '".$functionName[1]."' in class '".get_class($functionName[0])."'</font>");
                }
                $pass = true;
                continue;
            }
            if(strtolower(substr($line, 0, 7)) == "object ") {
                eval('$obj = '.substr($line, 7).';');
                if(is_array($functionName)) {
                    $functionName[0] = $obj;
                }
                continue;
            }
            if($line == "<?php") {
                $evalCode = true;
                $code = "";
                continue;
            }
            if($line == "?>") {
                $evalCode = false;
                eval($code);
                continue;
            }
            if($evalCode) {
                $code .= $line;
                continue;
            }
            if(empty($functionName)) {
                die("<font color='red'>Syntax Error: Line ".$lineNum." - Must specify a function or method to test using the directive '(function|method) [funcName]'</font>");
            }

            $line = str_replace('\\n', "\n", $line, $count);
            $matches = array();
            $regex = "\s*(`?)([^`]*?)\\1\s*(?:,|$)";
            if(!preg_match_all("/".$regex."/i", $line, $matches, PREG_PATTERN_ORDER) || empty($matches) || count($matches[2]) < 3) {
                die("<font color='red'>Syntax Error: Line ".$lineNum."</font><br>");
            }

            $test["title"] = $matches[2][0];
            $test["p"] = $matches[2][1];
            if($test["p"] == "null") {
                $test["p"] = null;
            }
            eval("\$temp = ".$matches[2][2].";");
            $test["r"] = $temp;
            $test["args"] = array();
            foreach(array_slice($matches[2], 3, -1) as $arg) {
                eval("\$arg = ".$arg.";");
                $test["args"][] = $arg;
            }
            $tmp = unitTest($functionName, $test);
            $pass = $tmp && $pass;
            if($tmp) {
                $numPassed++;
            } else {
                $numFailed++;
            }
        }

        if(is_array($functionName)) {
            $functionName[0] = $objName;
            $functionName = implode("->", $functionName);
        }
        if(isset($pass)) {
            print "-----------------------------<br>";
            passFail($pass, $functionName);
        }

        print "=============================<br>";
        passFail($filePass, $filename."php");

        return array("pass"=>$numPassed, "fail"=>$numFailed);
    }

    require_once("functions.php");

    $numTestsPassed = 0;
    $numTestsFailed = 0;
    $files = scandir("./");
    foreach($files as $key=>$file) {
        if(substr($file, -4) == ".utd") {
            $ret = testFile(substr($file, 0, -3));
            $numTestsPassed += $ret["pass"];
            $numTestsFailed += $ret["fail"];
        }
    }
    print "#############################<br>";
    print $numTestsPassed.' Tests: <font color="green">Passed</font><br>';
    if($numTestsFailed != 0) {
        print $numTestsFailed.' Tests: <font color="red">Failed</font><br>';
    }
?>