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
 * Handles client side interaction with the application as a whole.
 *
 * @author Bion Oren
 */
var lusa = {}

/**
 * Initializes the application state.
 *
 * @return VOID
 */
lusa.init = function() {
    //init the substitutable classes
    lusa.makeClassesDraggable();

    //init the course sequence classes
    $$(".classOverlay").each(function(course) {
        if(course.hasClassName("strike")) {
            return;
        }
        lusa.makeClassCompletable(course);
    });

    //init the completed classes
    $$(".ximage").each(function(course) {
        lusa.markUncompletableClass(course);
    });
};

/**
 * Marks all unused classes that have been taken as draggable.
 *
 * @return VOID
 */
lusa.makeClassesDraggable = function() {
    $$(".incompleteClass").each(function(course) {
        id = course.id;
        options = {revert: true, scroll: window};
        new Draggable(id, options);
    });
}

/**
 * Makes a class droppable and able to be completed.
 *
 * @return VOID
 */
lusa.makeClassCompletable = function(course) {
    degreeID = course.up("table").id;
    Droppables.add(course.id, {
        accept: "incompleteClass",
        hoverclass: "classSubHover",
        overlap: "vertical",
        /**
         * Called when a draggable is dropped on this course.
         *
         * @param OBJECT drag The element that was dragged.
         * @param OBJECT drop The element it was dropped on.
         * @param OBJECT event Information about the mouse event.
         * @return VOID
         */
        onDrop: function(drag, drop, event) {
            id = drag.down().getAttribute("data-id");
            target = drop.getAttribute("data-id");
            new Ajax.Updater(drop.down(), "postback.php", {
                method: "post",
                insertion: Insertion.Bottom,
                parameters: {mode: "completeClass", ID: id, target: target, degree: degreeID},
                /**
                 * Called when the request is known to be successful.
                 *
                 * @param OBJECT transport Response information from the server.
                 * @return VOID
                 */
                onSuccess: function(transport) {
                    drop.removeClassName("nostrike");
                    drop.addClassName("strike");
                    drop.down().removeClassName("hidden");
                    if(!lusa.isTransferCourse(drag)) {
                        if(drag.previous().hasClassName("deptHeader") && drag.next().hasClassName("deptHeader")) {
                            Element.remove(drag.previous());
                        }
                        Element.remove(drag);
                        Droppables.remove(drop);
                    }
                    hours = drop.getAttribute("data-hours");
                    lusa.addHours(drop.up("table").id, drop.previous(".semesterTitle"), -hours);
                }
            });
        }
    });
}

/**
 * Marks a class as ready to be uncompleted.
 *
 * @param OBJECT course The course that is complete that can now be uncompleted.
 * @return VOID
 */
lusa.markUncompletableClass = function(course) {
    Event.observe(course, "click", function(event) {
        degreeID = course.up("table").id;

        //uncomplete the original class
        course = this.up(".classOverlay");
        courseID = course.getAttribute("data-id");
        course.removeClassName("strike");
        course.addClassName("nostrike");
        course.down().addClassName("hidden");

        completingClass = this.next(".courseSub");
        completingClassID = completingClass.getAttribute("data-id");
        dept = completingClass.getAttribute("data-dept");
        number = completingClass.getAttribute("data-num");

        hours = course.getAttribute("data-hours");
        lusa.addHours(this.up("table").id, this.up(".classOverlay").previous(".semesterTitle"), hours);

        //remove the completing class' info
        ele = this.next()
        while(ele) {
            Element.remove(ele);
            ele = this.next()
        }

        lusa.insertIncompleteClass(courseID, completingClassID, dept, number);
        //make this class available to be completed again
        lusa.makeClassCompletable(course);
    });
}

/**
 * Adds a class back into the list of incomplete classes.
 *
 * @param INTEGER scheduledClassID ID of the class that was being completed
 * @param INTEGER completingClassID ID of the class that has been taken
 * @param STRING targetDept The deparmtent of said class.
 * @param INTEGER targetNum The course number of said class.
 * @return VOID
 */
lusa.insertIncompleteClass = function(scheduledClassID, completingClassID, targetDept, targetNum) {
    result = null;
    needDept = false;
    insertion = Insertion.Before;
    $$('.deptHeader').each(function(element) {
        dept = element.innerHTML.strip();
        if(result != null || dept < targetDept) {
            return;
        }
        if(dept == targetDept) {
            ele = element.next();
            while(!ele.hasClassName("deptHeader")) {
                lastele = ele;
                if(ele.down(".classNumber").innerHTML.strip().substring(2) > targetNum) {
                    result = ele;
                    break;
                }
                ele = ele.next();
            }
            if(result == null) {
                result = ele;
            }
        } else if(dept > targetDept) {
            result = element;
            needDept = true;
        }
    });
    if(result == null) {
        result = $('classSubs');
        needDept = true;
        insertion = Insertion.Bottom;
    }

    new Ajax.Updater(result, "postback.php", {
        method: "post",
        insertion: insertion,
        parameters: {mode: "uncompleteClass", classID: scheduledClassID, takenClassID: completingClassID, needDept: needDept},
        /**
         * Called when the request has completely finished processing.
         *
         * @param OBJECT transport Response information from the server.
         * @return VOID
         */
        onComplete: function(transport) {
            lusa.makeClassesDraggable();
        }
    });
}

/**
 * Marks a given number of hours as not being completed (adds them where appropriate).
 *
 * @param STRING csID The ID of the course sequence the class is in.
 * @param OBJECT semester The semester the class is in.
 * @param INTEGER hours The number of hours to add.
 * @return VOID
 */
lusa.addHours = function(csID, semester, hours) {
    hours = parseInt(hours);
    semHours = semester.next(".semesterHours")
    semHours.innerHTML = parseInt(semHours.innerHTML.strip().split(" ")[0]) + hours;
    semHours.innerHTML += " hours";
    $(csID+'-completedHours').innerHTML = parseInt($(csID+'-completedHours').innerHTML) - hours;
    $(csID+'-remainingHours').innerHTML = parseInt($(csID+'-remainingHours').innerHTML) + hours;
}

/**
 * Checks to see if the given course is the special transfer credit course.
 *
 * @param OBJECT course Course to check.
 * @return BOOLEAN True if this is the transfer credit course.
 */
lusa.isTransferCourse = function(course) {
    return course.down(".classDepartment").innerHTML.strip().endsWith("LETU") && course.down(".classNumber").innerHTML.strip().endsWith("4999")
}