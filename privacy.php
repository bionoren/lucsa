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

    session_start();
    $path = "./";
    if(!isset($_REQUEST["hideBack"])) {
        print "<a href='index.php'>Back</a><br><br>";
    }
?>
In order to facilitate scheduling your course sequence, we must know what classes you have already taken.
To that end, this script gathers information from your unofficial transcript at:
<br>
<a href='http://cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi'>cxweb.letu.edu/cgi-bin/student/stugrdsa.cgi</a>
<br>
None of this information is saved for longer than it takes to process the page. Only the course sequence identifiers
of the classes you have/are taking are stored securely (in a session variable) for a short period of time. No
information about your grades is stored anywhere or used by any part of this script.
<br><br>
To further ensure and protect your privacy, the source of the home page, which contains all the code used to parse
your transcript, is available by clicking the link below.
<br>
<a href='viewSource.php'>View Source</a>
<br><br>
The source for that page is:
<br>
<?php highlight_file("viewSource.php"); ?>
<br>