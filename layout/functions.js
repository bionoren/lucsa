var lusa = {}

lusa.init = function() {
    //init the substitutable classes
    lusa.makeClassesDraggable();

    //init the course sequence classes
    $$(".classOverlay").each(function(course) {
        if(course.hasClassName("strike")) {
            return;
        }
        lusa.makeClassDroppable(course);
    });

    //init the completed classes
    $$(".ximage").each(function(course) {
        lusa.markUncompletableClass(course);
    });
};

lusa.makeClassesDraggable = function() {
    $$(".incompleteClass").each(function(course) {
        id = course.id;
        options = {revert: true, scroll: window};
        new Draggable(id, options);
    });
}

lusa.makeClassDroppable = function(course) {
    degreeID = course.up("table").id;
    Droppables.add(course.id, {
        accept: "incompleteClass",
        hoverclass: "classSubHover",
        overlap: "vertical",
        onDrop: function(drag, drop, event) {
            dept = drag.down(".classDepartment").innerHTML.strip();
            num = drag.down(".classNumber").innerHTML.strip().substring(2);
            title = drag.down(".classTitle").innerHTML.strip().substring(2);
            new Ajax.Updater(drop.down(), "postback.php", {
                method: "post",
                insertion: Insertion.Bottom,
                parameters: {mode: "completeClass", dept: dept, num: num, title: title, target: drop.id, degree: degreeID},
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
                    hours = drop.down(".classNumber").innerHTML.strip();
                    hours = hours.substring(hours.length-1);
                    if(hours == "|") {
                        hours = num.substring(num.length-1);
                    }
                    lusa.addHours(drop.up("table").id, drop.previous(".semesterTitle"), -hours);
                }
            });
        }
    });
}

lusa.markUncompletableClass = function(course) {
    Event.observe(course, "click", function(event) {
        id = this.up().id;
        degreeID = course.up("table").id;
        new Ajax.Request("postback.php", {
            method: "post",
            parameters: {mode: "uncompleteClass", target: id, degree: degreeID},
            onSuccess: function(transport) {
                course = this.up(".classOverlay");
                course.removeClassName("strike");
                course.addClassName("nostrike");
                course.down().addClassName("hidden");
                dept = this.next(".classDepartment").innerHTML.strip();
                number = this.next(".classNumber").innerHTML.strip().substring(2);
                title = this.next(".classTitle").innerHTML.strip().substring(2);
                lusa.insertIncompleteClass(dept, number, title);

                hours = this.up().next(".classNumber").innerHTML.strip();
                hours = hours.substring(hours.length-1);
                if(hours == "|") {
                    hours = this.next(".classNumber").innerHTML.strip();
                    hours = hours.substring(hours.length-1);
                }
                lusa.addHours(this.up("table").id, this.up(".classOverlay").previous(".semesterTitle"), hours);

                ele = this.next()
                while(ele) {
                    Element.remove(ele);
                    ele = this.next()
                }
            }.bind(this),
            onComplete: function(transport) {
                lusa.makeClassDroppable(this.up(".classOverlay"));
            }.bind(this)
        });
    });
}

lusa.insertIncompleteClass = function(targetDept, targetNum, title) {
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
        parameters: {mode: "getClassFromDeptNum", dept: targetDept, num: targetNum, title: title, needDept: needDept},
        onComplete: function(transport) {
            lusa.makeClassesDraggable();
        }
    });
}

lusa.addHours = function(csID, semester, hours) {
    hours = parseInt(hours);
    semHours = semester.next(".semesterHours")
    semHours.innerHTML = parseInt(semHours.innerHTML.strip().split(" ")[0]) + hours;
    semHours.innerHTML += " hours";
    $(csID+'-completedHours').innerHTML = parseInt($(csID+'-completedHours').innerHTML) - hours;
    $(csID+'-remainingHours').innerHTML = parseInt($(csID+'-remainingHours').innerHTML) + hours;
}

lusa.isTransferCourse = function(course) {
    return course.down(".classDepartment").innerHTML.strip().endsWith("LETU") && course.down(".classNumber").innerHTML.strip().endsWith("4999")
}