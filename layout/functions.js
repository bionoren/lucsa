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
 * Confirms if the user wants to clear all class associations
 *
 * @return BOOLEAN True if all class associations should be cleared.
 */
function clearClassConfirm() {
    return confirm('Are you sure you want to clear all class associations?');
}

/**
 * Returns the value for a given URL GET parameter.
 *
 * @param STRING name Key for the value to get.
 * @return MIXED Value for the provided key, or null if they key was not found.
 */
function getURLParam(name) {
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = "[\\?&]"+name+"=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.href);
    if(results == null) {
        return null;
    } else {
        return results[1];
    }
}

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
    new Control.Tabs('tabbar');

    //init the substitutable classes
    lusa.makeClassesDraggable();

    //init the course sequence classes
    $$(".classOverlay").each(function(course) {
        lusa.makeClassMovable(course);
        if(course.hasClassName("strike")) {
            return;
        }
        lusa.makeClassCompletable(course);
    });
    //WARNING: don't create semester droppables before this comment!
    lusa.makeSemestersDroppable();

    //init the completed classes
    $$(".ximage").each(function(course) {
        lusa.markUncompletableClass(course);
    });

    //init the class overlays
    $$(".hovercard").each(function(overlay) {
        lusa.activateOverlay(overlay);
    });
    Event.observe(document.body, "click", function(event) {
        if(lusa.globalClassPopover && !lusa.disableNextClassPopoverClose) {
            lusa.globalClassPopover.setStyle({
                visibility: "hidden"
            });
        } else {
            lusa.disableNextClassPopoverClose = false;
        }
    });
};

/** @var Element Reference to the currently visible class popover. */
lusa.globalClassPopover = null;

/**
 * @var Disables the document.body class overlay close handler once.
 * Used to prevent the overlay from closing due to other events.
 */
lusa.disableNextClassPopoverClose = false;

/**
 * Sets up event handlers to show and hide a class popover.
 *
 * @param Element overlay Container for the popover content.
 * @return VOID
 */
lusa.activateOverlay = function(overlay) {
    Event.observe(overlay.next(".classSummary"), "click", function(event) {
        if(lusa.globalClassPopover) {
            lusa.globalClassPopover.setStyle({
                visibility: "hidden"
            });
        }
        lusa.globalClassPopover = overlay;
        overlay.setStyle({
            visibility: "visible"
        });
        event.stop();
    });
    Event.observe(overlay, "click", function(event) {
        lusa.globalClassPopover.setStyle({
            visibility: "hidden"
        });
        event.stop();
    });
};

/**
 * Evaluates if this is the summary view or not.
 *
 * @return BOOLEAN True if summary view.
 */
lusa.isSummaryView = function() {
    mode = getURLParam("disp");
    return mode == null || mode == "summary";
};

/**
 * Makes the semesters droppable for rearranging classes between semesters.
 *
 * @return VOID
 */
lusa.makeSemestersDroppable = function() {
    if(!lusa.isSummaryView()) {
        return;
    }

    $$(".semesterClasses").each(function(semester) {
        Droppables.add(semester.id, {
            accept: "classOverlay",
            overlap: "vertical",
            onDrop: function(drag, drop, event) {
                semesterID = drop.getAttribute("data-id");
                oldSemesterID = drag.up().getAttribute("data-id");
                if(semesterID == oldSemesterID) {
                    return;
                }
                classID = drag.getAttribute("data-id");
                degreeID = drop.up("table").id;

                new Ajax.Request("postback.php", {
                    method: "post",
                    parameters: {mode: "moveClass", degree: degreeID, course: classID, semester: semesterID, oldSemester: oldSemesterID},
                    onSuccess: function(transport) {
                        //update the semester hours
                        hours = parseInt(drag.getAttribute("data-hours"));
                        newHours = parseInt(drop.getAttribute("data-hours"));
                        oldHours = parseInt(drag.up().getAttribute("data-hours"));
                        if(newHours == 0 && hours > 0) {
                            drop.down(".semesterTitle").removeClassName("strike");
                            drop.down(".semesterHours").removeClassName("strike");
                        }
                        if(hours > 0 && oldHours == hours) {
                            drag.up().down(".semesterTitle").addClassName("strike");
                            drag.up().down(".semesterHours").addClassName("strike");
                        }
                        newHours += hours;
                        oldHours -= hours;
                        drop.down(".semesterHours").innerHTML = newHours+" hours";
                        drag.up().down(".semesterHours").innerHTML = oldHours+" hours";
                        drop.setAttribute("data-hours", newHours);
                        drag.up().setAttribute("data-hours", oldHours);
                        //move the class
                        drop.appendChild(drag);
                    }
                });
            }
        });
    });
}

/**
 * Makes a class movable within the semester list.
 *
 * @param OBJECT course The course that can be dragged between semesters.
 * @return VOID
 */
lusa.makeClassMovable = function(course) {
    if(!lusa.isSummaryView()) {
        return;
    }

    new Draggable(course.id, {
        revert: true,
        scroll: window,
        handle: "classSummary",
        onEnd: function(draggable, event) {
            //apparently onEnd isn't actually the end...
            setTimeout(function() {
                course.style.zIndex = 50
            }, 100);
        }
    });
}

/**
 * Marks all unused classes that have been taken as draggable.
 *
 * @return VOID
 */
lusa.makeClassesDraggable = function() {
    $$(".incompleteClass").each(function(course) {
        new Draggable(course.id, {
            revert: true,
            scroll: window
        });
    });
}

/**
 * Makes a class droppable and able to be completed.
 *
 * @param OBJECT course The course that can be completed.
 * @return VOID
 */
lusa.makeClassCompletable = function(course) {
    degreeID = course.up("table").id;
    Droppables.add(course.down(".bd").id, {
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
            course = drop.up(".classOverlay");
            target = course.getAttribute("data-id");
            new Ajax.Updater(drop.up().down(".completingCourseContainer"), "postback.php", {
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
                    course.removeClassName("nostrike");
                    course.addClassName("strike");
                    drop.next(".completingCourseContainer").removeClassName("hidden");
                    if(!lusa.isTransferCourse(drag)) {
                        if(drag.previous().hasClassName("deptHeader") && drag.next().hasClassName("deptHeader")) {
                            Element.remove(drag.previous());
                        }
                        Element.remove(drag);
                        Droppables.remove(drop);
                    }
                    hours = course.getAttribute("data-hours");
                    lusa.addHours(course.up("table").id, course.previous(".semesterTitle"), -hours);
                }
            });
            lusa.disableNextClassPopoverClose = true;
        }
    });
}

/**
 * Marks a class as ready to be uncompleted.
 *
 * @param OBJECT course The course that is complete that can now be uncompleted.
 * @return VOID
 */
lusa.markUncompletableClass = function(closeButton) {
    Event.observe(closeButton, "click", function(event) {
        degreeID = closeButton.up("table").id;

        //uncomplete the original class
        course = closeButton.up(".classOverlay");
        courseID = course.getAttribute("data-id");
        course.removeClassName("strike");
        course.addClassName("nostrike");
        course.down(".completingCourseContainer").addClassName("hidden");

        completingClass = course.down(".courseSub");
        completingClassID = completingClass.getAttribute("data-id");
        dept = completingClass.getAttribute("data-dept");
        number = completingClass.getAttribute("data-num");

        hours = course.getAttribute("data-hours");
        lusa.addHours(this.up("table").id, this.up(".classOverlay").previous(".semesterTitle"), hours);

        //remove the completing class' info
        ele = course.down(".courseSub");
        while(ele) {
            Element.remove(ele);
            ele = this.next()
        }

        lusa.insertIncompleteClass(courseID, completingClassID, dept, number);
        //make this class available to be completed again
        lusa.makeClassCompletable(course);
        event.stop();
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