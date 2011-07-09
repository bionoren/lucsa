Requires PHP >= 5.2.x where I'm not sure what x is.
Optionally uses PHP >= 5.3.1 for bundled sqlite >= 3.6.19 support, which includes foreign keys (or PHP >= 5.3 with SQLite >= 3.6.19 bindings)
NOTE: PHP >= 5.3.3 is STRONGLY recommended because of stability concerns with 5.3.1 through 5.3.2
requires Smarty >= 3.0
requires Prototype >= 1.6
requires Scriptaculous >= 1.5

requires Doxygen >= 1.7.2 to generate documentation
requires the DOT tool (part of graphviz) to generate graphs in documentation


CODE ORGANIZATION:

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