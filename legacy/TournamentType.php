<?php

/**
 * Description of TournamentType:
 * This class implements the abstract class of all
 * different tournament generators for different
 * types. It implements the basic functionality
 * and defines the interface between the TourGen
 * (tournament generator) and a TournamentType
 * child class.
 *
 * @author jurrien
 */
abstract class TournamentType
{
    protected $myTourID;

    /** @var Connection */
    protected $myConnection;

    /* ---------------------------------------------------------------------- */
    public function TournamentType() {}

    /* ---------------------------------------------------------------------- */

    abstract public function genMatches();

    abstract public function getMatchList();

    abstract public function updateNextRound();

    /* ---------------------------------------------------------------------- */
    public function withTourID($id)
    {
        $this->myTourID = $id;

        return $this;
    }

    public function withConnection($conn)
    {
        $this->myConnection = $conn;

        return $this;
    }
}
