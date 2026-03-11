<?php

require 'TournamentType.php';
require 'Match.php';

require 'Competition/HalfCompetition.php';
require 'Competition/FullCompetition.php';
require 'Competition/Pool.php';

require 'KO/SingleKOGen.php';
require 'KO/TreeNode.php';

require __DIR__.'/../utils/list_t.php';

require 'Scheduler.php';

require 'PlayOff/PlayOff.php';

/**
 * Description of TourGen
 *
 * @author jurrien
 */
class TourGen
{
    private $poolList;

    private $myConnection;

    private $myTourID;

    /* ----------------------------------------------------------------------- */
    public function TourGen()
    {
        $this->poolList = [];
        $this->myConnection = new Connection;
    }

    /* ----------------------------------------------------------------------- */
    public function genMatches()
    {
        $tour = $this->myConnection->getOneElement('tournament', $this->myTourID);

        switch ($tour['type']) {
            case 0:
                $generator = new HalfCompetition;
                break;
            case 1:
                $generator = new FullCompetition;
                break;
            case 2:
                $generator = new SingleKOGen;
                break;
            case 4:
                $generator = new PlayOff;
                break;
            default: exit('not implmented yet');
        }

        $generator->withConnection($this->myConnection)
            ->withTourID($this->myTourID)
            ->genMatches();
        $this->poolList = $generator->getMatchList();

        //        echo "</br></br>gen</br>";
        //        foreach($this->poolList as $match)
        //            echo $match->getTeam1() . " - " . $match->getTeam2() . "</br>";
        //        echo "</br></br></br>";
    }

    public function updateCounter(User $user)
    {
        $mTable = $this->myConnection->getRestTableContant('match', true,
            $user, "tourID='$this->myTourID' AND isPlayed='0'", '');
        if (mysql_num_rows($mTable) == 0) {
            $uRecord = $this->myConnection->getOneElement('useraccount', $user->getUserID());
            $this->myConnection->updateElement('useraccount', $user->getUserID(),
                ['numCompleted' => $uRecord['numCompleted'] + 1]);
        }
    }

    /* ----------------------------------------------------------------------- */
    public function schedule()
    {

        //        echo "</br></br>schedule</br>";
        //        foreach($this->poolList as $match)
        //            echo $match->getTeam1() . " - " . $match->getTeam2() . "</br>";
        //        echo "</br></br></br>";
        $scheduler = new Scheduler;
        $scheduler->withConnection($this->myConnection)
            ->withTourID($this->myTourID)
            ->withMatchList($this->poolList)
            ->reqTourInfo();

        $scheduler->reqScheduledTime();

        if ($_POST['action'] == 'genMatches') {
            mysql_query("DELETE FROM `match` WHERE `tourID`='$this->myTourID'");

            $dayList = $this->myConnection->getRestTableContant('daylist', false, null,
                "tourID='$this->myTourID'", 'ORDER BY date, startTime');
            $day = mysql_fetch_array($dayList);

            $scheduler->makeSchedule();
            $this->myConnection->updateElement('tournament', $this->myTourID,
                ['isGenerated' => 1, 'date' => $day['date'], 'startTime' => $day['startTime'],
                    'matchLength' => $_POST['matchLength'], 'pauseLength' => $_POST['pauseLength'],
                    'pauseFinal' => $_POST['pauseFinal']]);

            return;
        } elseif ($_POST['action'] == 'length') {
            $time = $scheduler->calcLeftTime();
        } else {
            $time = $scheduler->calcMatchLength();
        }

        echo json_encode($time);
    }

    /* ----------------------------------------------------------------------- */
    public function generateMatches()
    {
        //        die('hier!!!!!');
        $scheduler = new Scheduler;
        $scheduler->withConnection($this->myConnection)
            ->withTourID($this->myTourID)
            ->withMatchList($this->poolList)
            ->reqTourInfo();

        /** set state back to startUp to make sure that edit wil start at the begining */
        $this->myConnction->updateElement('tournament', $this->myTourID,
            ['state' => 0]);

        $scheduler->reqScheduledTime();
        $scheduler->makeSchedule();
    }

    /* ----------------------------------------------------------------------- */
    public function updateNextRound()
    {
        $this->myTourID = $_POST['index'];
        $tour = $this->myConnection->getOneElement('tournament', $this->myTourID);

        switch ($tour['type']) {
            case '0':
                $updater = new HalfCompetition;
                break;
            case '1':
                $updater = new FullCompetition;
                break;
            case '2':
                $updater = new SingleKOGen;
                break;
            case 4:
                $updater = new PlayOff;
                break;
            default: exit('error: undifined tournament type in tournament '.$tour['name']);
        }

        return $updater->withConnection($this->myConnection)
            ->withTourID($this->myTourID)
            ->updateNextRound();
    }

    /* ----------------------------------------------------------------------- */
    public function withTourID($id)
    {
        $this->myTourID = $id;

        return $this;
    }
}

if (isset($_POST['action']) && (($_POST['action'] == 'genMatches')
                            || ($_POST['action'] == 'length')
                            || $_POST['action'] == 'end')) {
    require '../Database/Connection.php';
    //    echo "start of things </br>";
    $generator = new TourGen;
    $generator->withTourID($_POST['tourID'])
        ->genMatches();
    $generator->schedule();
}
