function substituteClass(dragged, dropped, overlap) {
    if(overlap === true || overlap >= 0.5) {
        var draggedIDs = dragged.id.split("~");
        var droppedIDs = dropped.id.split("~");
        var draggedID = draggedIDs[0];
        var draggedUID = draggedIDs[1];
        var droppedID = droppedIDs[0];
        var droppedUID = droppedIDs[1];

        if(overlap === true) {
            dropped.removeClassName("nostrike");
            dropped.addClassName("strike");

            $(dropped.id+"complete").insert(dragged.innerHTML);
            $(dropped.id+"complete").removeClassName("hidden");
            //make the ajax call
        }
    }
}

function handleDropEvent(dragged, dropped, event) {
    substituteClass(dragged, dropped, true);
}