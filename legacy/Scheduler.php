<?php

/**
 * This class is responseble for everything that
 * has anything to do with assining times, days
 * fields and referees to a match. Also calculating
 * the complete time of a tournament and the calculation
 * of the matchlength is done in this class.
 *
 * @author Jurrien
 */
class Scheduler {

    private $myConnection;
    private $myTourID;
    private $myMatchList;
    private $numFinalPause;
    private $tournament;
    private $scheduledTime;

    /* ----------------------------------------------------------------------- */

    public function reqTourInfo() {
        $this->tournament = $this->myConnection->getOneElement("tournament", $this->myTourID);
        return $this;
    }

    /* ----------------------------------------------------------------------- */

    public function reqScheduledTime() {
        $dayList = $this->myConnection->getRestTableContant("daylist", false, null, "tourID='$this->myTourID'", "");

        /** calc scheduled time as timestamp */
        $time = 0;
        while ($day = mysql_fetch_array($dayList)) {
            $start = strtotime($day['startTime']);
            $end = strtotime($day['endTime']);
            $time += ($end - $start);
        }

        /** calc scheduled time in min */
        $this->scheduledTime = $time / 60;
    }

    /* ----------------------------------------------------------------------- */

    public function calcMatchLength() {
        $fieldList = $this->myConnection->getRestTableContant("fieldlist", false, null, "tourID='$this->myTourID'", "");
        $numFields = mysql_num_rows($fieldList);

        $timeSlotList = $this->list_scheduler($numFields);

        $matchPause = $_POST['pauseLength'];
        $finalPause = $_POST['pauseFinal'];

        /** first estimate ideal time */
        $playTime = $this->scheduledTime - ($matchPause * (count($timeSlotList) - 2) + $finalPause);
        $matchLength = intval($playTime / count($timeSlotList));

        do {
            $timeInfo = $this->scheduleMatches($numFields, $matchLength, $_POST['pauseLength'], $_POST['pauseFinal']);
            $timeToSchedule = $timeInfo['toschedule'];
            $matchLength--;
            if ($matchLength == 0) {
                return array("match" => -1, "h" => 0, "m" => 0);
            }
        } while ($timeToSchedule > 0);
        $matchLength++;

        return array("match" => $matchLength, "h" => intval($timeInfo['left'] / 3600),
            "m" => (($timeInfo['left'] / 60) % 60));
    }

    /* ----------------------------------------------------------------------- */

    public function calcLeftTime() {
        /** gether info */
        $fieldList = $this->myConnection->getRestTableContant("fieldlist", false, null, "tourID='$this->myTourID'", "");
        $numFields = mysql_num_rows($fieldList);

        $timeInfo = $this->scheduleMatches($numFields, $_POST['matchLength'], $_POST['pauseLength'], $_POST['pauseFinal']);

        if ($timeInfo['toschedule'] > 0) {
            $time = array("type" => "needed", "m" => (($timeInfo['toschedule'] / 60) % 60),
                "h" => intval($timeInfo['toschedule'] / 3600));
        } else {
            $time = array("type" => "left", "m" => (($timeInfo['left'] / 60) % 60),
                "h" => intval($timeInfo['left'] / 3600));
        }

        return $time;
    }

    /* ----------------------------------------------------------------------- */

    private function scheduleMatches($numFields, $matchLength, $matchPause, $finalPause) {

        $timeSlotList = $this->list_scheduler($numFields);

        $dayList = $this->myConnection->getRestTableContant("daylist", false, null, "tourID='$this->myTourID'", "ORDER BY date, startTime");

        $day = array("endTime" => "0");
        $currTime = strtotime("0") + 1;

        for ($i = 0; $i < count($timeSlotList); $i++) {
            if (($currTime + ($matchLength * 60)) > (strtotime($day['endTime']))) {
                /** @NOTE no accidental assignemnt! */
                if (($day = mysql_fetch_array($dayList))) {
                    $currTime = strtotime($day['startTime']);

                    if (($currTime + ($matchLength * 60)) > (strtotime($day['endTime']))) {
                        /** redo this slot! */
                        $i--;
                        continue;
                    }
                } else {
                    break;
                }
            }

//            echo date("H:i:s", $currTime) . "</br>";
            foreach ($timeSlotList[$i] as $match) {
                $match->setDate($day['date'], date("H:i:s", $currTime));
//                echo "round " . $match->getRound() . " " . $match->getType() . " time " . $day['date'], date("H:i:s", $currTime) . " </br>";
            }
            $currTime += ($matchLength * 60);

            if (isset($timeSlotList[$i + 1][0]) && ($timeSlotList[$i + 1][0]->getType() == "final"))
                $currTime += ($finalPause * 60);
            else
                $currTime += ($matchPause * 60);
            /**
             * @NOTE $time left can now be smaller as 0 but that means that a 
             * pause is scheduled outside the availiable time which is not a
             * problem.
             */
        }
        $currTime -= ($matchPause * 60);

        if ((strtotime($day['endTime'])) > $currTime) {
            $timeLeft = (strtotime($day['endTime'])) - $currTime;
        }
        else
            $timeLeft = 0;

        while ($day = mysql_fetch_array($dayList))
            $timeLeft += (strtotime($day['endTime']) - strtotime($day['startTime']));

        /** @NOTE $i is not changed, thus continuing with the prev for loop */
        $timeToSchedule = 0;
        if (isset($timeSlotList[$i][0])) {
            $currType = $timeSlotList[$i][0]->getType();
        }
        $pause = 0;
        for ($i; $i < count($timeSlotList); $i++) {
            $timeToSchedule += ($matchLength * 60);
            if (isset($timeSlotList[$i + 1][0]) && ($timeSlotList[$i + 1][0]->getType() != $timeSlotList[$i][0]->getType()))
                $pause = ($finalPause * 60);
            else
                $pause = ($matchPause * 60);

            $timeToSchedule += $pause;

            $currType = $timeSlotList[$i][0]->getType();
        }
        $timeToSchedule -= $pause;
        return array("left" => $timeLeft, "toschedule" => $timeToSchedule,
            "schdule" => $timeSlotList);
    }

    /*
     * The next function are for the improved schedule algorithm
     */

    /* ----------------------------------------------------------------------- */

    private function countNumPoolMatch(array $list) {
        $c = 0;
        foreach ($list as $element) {
            if ($element->getType() == 'pool')
                $c++;
        }

        return $c;
    }

    /* ----------------------------------------------------------------------- */

    private function devide_work(array $matchList) {
        $mPerPool = array();
        foreach ($matchList as $match) {
            if (!isset($mPerPool[$match->getPool()]))
                $mPerPool[$match->getPool()] = array();

            $mPerPool[$match->getPool()][] = $match;
        }

        $nList = array();
        $mCounter = 0;
        $pCounter = 0;
        $i = 0;
        while ($mCounter < count($matchList)) {
            foreach ($mPerPool as $pool) {
                if (isset($pool[$pCounter])) {
                    $nList[$mCounter] = $pool[$pCounter];
                    $mCounter++;
                }
            }
            $pCounter++;
        }

        return $nList;
    }

    /* ----------------------------------------------------------------------- */

    private function list_scheduler($numFields) {
        $timeSlot = array();

//        echo "</br></br></br>";
//        foreach($this->myMatchList as $match)
//            echo $match->getTeam1() . " - " . $match->getTeam2() . " pool: " . $match->getPool() . "</br>";
//        echo "</br></br></br>";

        $scheduledTeams = new list_t();
        $stilToSchedule = new list_t();
        $matchList = new list_t($this->myMatchList);

        $mCounter = 0;
        $sCounter = 0;
        $rCounter = 0;

//        echo "$sCounter: </br>";
        while ($mCounter < count($this->myMatchList)) {
            while ($match = $stilToSchedule->get_next()) {

                if (!isset($timeSlot[$sCounter]))
                    $timeSlot[$sCounter] = array();

                if (($match->getType() != 'pool') && ($this->countNumPoolMatch($stilToSchedule->get_array()) > 0)) {
                    continue;
                } elseif (($match->getType() != 'pool') && $this->countNumPoolMatch($timeSlot[$sCounter]) > 0) {
                    $stilToSchedule->rewind_iteration();
                    $match = $stilToSchedule->get_next();
                    $scheduledTeams->clear();
                    $rCounter = 0;
                    $sCounter++;
//                    echo "$sCounter: </br>";
                }


                if ($scheduledTeams->arr_in_list($match->getDependency()) == 0) {
                    $timeSlot[$sCounter][] = $match;
//                    echo $match->getTeam1() . " - " . $match->getTeam2() . "||</br>";
                    $scheduledTeams->add_arr($match->getDependency());
                    $stilToSchedule->remove($match);
                    $rCounter++;
                    $mCounter++;
                }

                if ($rCounter == $numFields) {
                    $stilToSchedule->rewind_iteration();
                    $scheduledTeams->clear();
                    $rCounter = 0;
                    $sCounter++;
//                    echo "$sCounter: </br>";
                }
            }

            if (($match = $matchList->get_next())) {
                if (!isset($timeSlot[$sCounter]))
                    $timeSlot[$sCounter] = array();

                if (($match->getType() != 'pool') && ($this->countNumPoolMatch($matchList->get_array()) > 0)) {
                    $stilToSchedule->add($match);
                    continue;
                } elseif (($match->getType() != 'pool') && $this->countNumPoolMatch($timeSlot[$sCounter]) > 0) {
                    $sCounter++;
                    $timeSlot[$sCounter] = array();
                }

                if ($scheduledTeams->arr_in_list($match->getDependency()) == 0) {
                    $timeSlot[$sCounter][] = $match;
//                    echo $match->getTeam1() . " - " . $match->getTeam2() . "</br>";
                    $scheduledTeams->add_arr($match->getDependency());
                    $rCounter++;
                    $mCounter++;
                } else {
                    $stilToSchedule->add($match);
                }

                if ($rCounter == $numFields) {
                    $stilToSchedule->rewind_iteration();
                    $scheduledTeams->clear();
                    $rCounter = 0;
                    $sCounter++;
//                    echo "$sCounter: </br>";
                }
            } else {
                $stilToSchedule->rewind_iteration();
                $scheduledTeams->clear();
                $rCounter = 0;
                $sCounter++;
//                echo "no m, $sCounter: </br>";
            }
        }

        if ($this->tournament['withFinal'] == 2)
            $timeSlot[] = array($this->setThirdPlaceMatch ());

        return $timeSlot;
    }

    /* ----------------------------------------------------------------------- */

    private function setThirdPlaceMatch() {
        $match = new Match(0, 0);
        $match->setType('third');
        $match->setPool(-1000);
        return $match;
    }

    /*
     * End of new functions
     */

    /* ----------------------------------------------------------------------- */

    private function asap($numFields) {
        $timeSlot = array();

//        echo "</br></br></br>";
//        foreach($this->myMatchList as $match)
//            echo $match->getTeam1() . " - " . $match->getTeam2() . "</br>";
//        echo "</br></br></br>";

        /** build match per round list */
        $roundList = array();
        foreach ($this->myMatchList as $match) {
            if (!isset($roundList[$match->getRound()])) {
                $roundList[$match->getRound()] = array();
            }
            $roundList[$match->getRound()][] = $match;
        }

        /** make time slot list */
        $slotCounter = 0;
        $matchCounter = 0;
        foreach ($roundList as $round) {
            foreach ($round as $match) {
                if (!isset($timeSlot[$slotCounter])) {
                    $timeSlot[$slotCounter] = array();
                }
//                echo "round " . $match->getRound() . " " . $match->getType() . " slot " . $slotCounter . " </br>";
                $timeSlot[$slotCounter][] = $match;
                $matchCounter++;

                if ($matchCounter == $numFields) {
                    $slotCounter++;
                    $matchCounter = 0;
                }
            }
            $matchCounter = 0;
            if (isset($timeSlot[$slotCounter])) {
                $slotCounter++;
            }
        }
//        echo "</br>---------</br></br>";

        return $timeSlot;
    }

    /* ----------------------------------------------------------------------- */

    public function makeSchedule() {
        /** gether info */
        $dayList = $this->myConnection->getRestTableContant("daylist", false, null, "tourID='$this->myTourID'", "ORDER BY date, startTime");

        $fieldList = $this->myConnection->getRestTableContant("fieldlist", false, null, "tourID='$this->myTourID'", "");
        $fieldArray = array();
        while ($field = mysql_fetch_array($fieldList)) {
            $fieldArray[] = $field['fieldID'];
        }

        $refereeList = $this->myConnection->getRestTableContant("refereelist", false, null, "tourID='$this->myTourID'", "");
        $refereeArray = array();
        while ($referee = mysql_fetch_array($refereeList)) {
            $refereeArray[] = $referee['refereeID'];
        }
        for ($i = count($refereeArray); $i < count($fieldArray); $i++) {
            $refereeArray[] = 0;
        }

        $mCounter = 1;
        $fCounter = 0;
        $rCounter = 0;

        $timeInfo = $this->scheduleMatches(count($fieldArray), $_POST['matchLength'], $_POST['pauseLength'], $_POST['pauseFinal']);
        $timeSlotList = $timeInfo['schdule'];

        foreach ($timeSlotList as $timeSlot) {
            foreach ($timeSlot as $match) {
                $matchTime = $match->getDate();
//                echo $match->getTeam1() . " " . $match->getTeam2() . " </br>";
//                echo "</br>$mCounter:";
//                var_dump($matchTime);
                $this->myConnection->insertElement("match", array(
                    "matchNum" => $mCounter,
                    "matchCode" => $match->getCode(),
                    "userID" => $this->tournament['userID'],
                    "team1ID" => $match->getTeam1(),
                    "team2ID" => $match->getTeam2(),
                    "fieldID" => $fieldArray[$fCounter],
                    "refereeID" => $refereeArray[$rCounter],
                    "pool" => $match->getPool(),
                    "tourID" => $this->myTourID,
                    "time" => $matchTime['time'],
                    "date" => $matchTime['date']
                ));

                $fCounter++;
                $rCounter++;
                $mCounter++;
                /** @NOTE the timeslots are created by using the number of fields */
                if ($rCounter >= count($refereeArray)) {
                    $rCounter = 0;
                }
            }

            $fCounter = 0;
        }
    }

    /* ----------------------------------------------------------------------- */

    public function withConnection($conn) {
        $this->myConnection = $conn;
        return $this;
    }

    public function withTourID($id) {
        $this->myTourID = $id;
        return $this;
    }

    public function withMatchList($matchList) {
        $this->myMatchList = $matchList;
        return $this;
    }

}

?>
