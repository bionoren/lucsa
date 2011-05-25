{extends file="courseSequence.tpl"}
{block name="body"}
    $allClasses = new ClassList();
    foreach($this->semesters as $semester) {
        $allClasses = ClassList::merge($allClasses, $semester->getClasses());
    }
    $allClasses->sort();
    $i = 2;
    $count = floor($allClasses->count()/2);
    print '<tr style="vertical-align:top;">';
        print '<td>';
            print '<table>';
                foreach($allClasses as $class) {
                    print $class->display($this->year);
                    $totalHours += $class->getHours();
                    if($class->isComplete()) {
                        $hoursCompleted += $class->getHours();
                    }
                    if($i++ == $count) {
                        print '</table></td><td><table>';
                    }
                }
            print '</table>';
        print '</td>';
    print '</tr>';
{/block}