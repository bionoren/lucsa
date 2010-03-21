<?php
    print str_replace("  ", "&nbsp;&nbsp;", nl2br(htmlentities(file_get_contents("index.php"))));
?>