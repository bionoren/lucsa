<!DOCTYPE html>
<html lang="en">
    <head>
        <title>LUCSA</title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
        <meta name="language" content="en"/>
        <meta name="description" content="Helps LETU students figure out their major and course sequence"/>
        <meta name="keywords" content="LETU LeTourneau student schedule class classes course sequence major minor"/>
        <link rel="stylesheet" type="text/css" href="layout/screen.css" media="screen,projection"/>
        <!-- cat prototype.js scriptaculous.js effects.js livepipe.js tabs.js builder.js controls.js dragdrop.js slider.js | java -jar ../yuicompressor-2.4.2.jar --type js > compiled.js -->
        <script src="layout/libs/compiled.js" type="text/javascript"></script>
        <script src="layout/functions.js" type="text/javascript"></script>
    </head>
    <body lang="en" onload="lusa.init();">
        <noscript>
            <span style="color:red; font-weight:bold; width:100%; text-align:center;">
                Help, Help!!! I'm trapped in a box without Javascript!
            </center>
        </noscript>
        <div class="wrapper">
            {block name="body"}{/block}
        </div>
        <div class="footer">
            <br>
            <a href='privacy.php'>Privacy Policy</a>
        </div>
    </body>
</html>