TABLE OF CONTENTS:
----------------------------------------------
1. REQUIREMENTS
1.1 CORE
1.2 DOCUMENTATION
2. INSTALLING
3. TROUBLESHOOTING
4. CODE ORGANIZATION

1. REQUIREMENTS:
----------------------------------------------
1.1 CORE:
----------------------------------------------
Requires PHP >= 5.2.x where I'm not sure what x is.
Optionally uses PHP >= 5.3.1 for bundled sqlite >= 3.6.19 support, which includes foreign keys (or PHP >= 5.3 with SQLite >= 3.6.19 bindings)
NOTE: PHP >= 5.3.3 is STRONGLY recommended because of stability concerns with 5.3.1 through 5.3.2
requires Smarty >= 3.0
requires Prototype >= 1.6
requires Scriptaculous >= 1.5

1.2 DOCUMENTATION:
----------------------------------------------
requires Doxygen >= 1.7.2 to generate documentation
requires the DOT tool (part of graphviz) to generate graphs in documentation

2. INSTALLING:
----------------------------------------------
Make sure that the cache/ and templates_c/ directories exist and are readable and writable by PHP
ensure that db/lucsa.sqlite is writable by PHP
Run deepCache.php
    NOTE: Initially, this will take a LONG time, as it has to pull down the entire LETU course catalog into your local cache.
          Just keep running it until it stops timing out.
You're ready to go!

3. TROUBLESHOOTING
----------------------------------------------
If you get into trouble, run deepCache.php again. deepCache reloads all the coure catalog data and resets all user data.

4. CODE ORGANIZATION:
----------------------------------------------
Code in classes is structured in the following way:

dependencies (require statements)
class {
    constants (alphabetized)
    static variables (alphabetized)
    instance variables (alphabetized)
    constructor
    class and instance methods (alphabetized)
    magic methods (except __toString and __destruct)
    __toString
    __destruct
}