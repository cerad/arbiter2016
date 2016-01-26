<?php

namespace AppBundle\Action\Avail;

class AvailLoaderExcel
{
  protected $date     = null;
  protected $official = null;

  protected $officials = [];

  protected function processRow($results,$row)
  {
    if (count($row) < 7) {
      print_r($row); die('Short row');
    }
    if ($row[4] === 'Officials Availability Report') {
      return;
    }
    if ($row[6] === 'Created by ArbiterSports.com')  {
      return;
    }

    if (($row[0] === 'Official' && $row[1] == 'Rank')) {
      return;
    }
    $colName  = trim($row[0]);
    $colRank  = trim($row[1]);
    $colAvail = trim($row[7]);

    if (substr($colAvail,0,12) == 'Open All Day') {
      $colAvail = 'Open All Day';
    }
    if ($colAvail === 'Blocked   12:00A 11:59P') {
      $colAvail = 'Blocked ALL DAY';
    }
    // Date change
    if (strpos($colName,'Referee Availability for') !== false) {
      $this->addOfficial();
      $date = trim(strrchr($colName,' '));
      $dt = \DateTime::createFromFormat('m/d/Y',$date);
      $this->date = $results->dates[] = $dt->format('Y-m-d');
      return;
    }

    // Both name and rank
    if ($colName && $colRank) {
      $this->addOfficial();

      $this->official = $official = [
        'name' => $colName,
        'rank' => $colRank,
        'city' => $row[ 4],
        'cell' => $row[10],
        'home' => $row[12],
        'avail'=> [],
      ];
      $this->official['avail'][$this->date][] = $colAvail;
      return;
    }
    if ($colName) {

      $name = $this->official['name'];

      // Drop trailing comma
      if (substr($name,-1) === ',') $name = substr($name,0,strlen($name)-1);

      $name = strchr($name,',') === false ? $name . ', ' . $colName : $name . ' ' . $colName;

      $this->official['name'] = $name;
    }
    if ($colAvail) {
      $this->official['avail'][$this->date][] = $colAvail;
    }
    return;

    //print_r($row); die();
  }
  protected function addOfficial()
  {
    if (!$this->official) return;

    $official = $this->official;
    $name = $official['name'];

    if (!isset($this->officials[$name])) {
      $this->officials[$name] = $official;
    }
    else {
      $date = $this->date;
      $this->officials[$name]['avail'][$date] = $official['avail'][$date];
    }
    $this->official = null;
  }
  public function load($filename,$basename = null)
  {
    $results = new AvailLoaderResults();
    $results->filename = $filename;
    $results->basename = $basename;

    // Tosses exception
    $reader = \PHPExcel_IOFactory::createReaderForFile($filename);
    $reader->setReadDataOnly(true);

    $wb = $reader->load($filename);
    $ws = $wb->getSheet(0);

    /*
    foreach($ws->getRowIterator() as $row) {
      $cells = $row->getCellIterator();
      $cells->setIterateOnlyExistingCells(false);
      $data = [];
      foreach($cells as $cell) {
        $data[] = $cell->getValue();
      }
      $this->processRow($results,$data);
    }
    */
    $rowMax = $ws->getHighestRow();
    $colMax = $ws->getHighestColumn(); // Letter
  //echo sprintf("WS rows %d, cols %s\n",$rowMax,$colMax);

    for($row = 1; $row < $rowMax; $row++) {
      $range = sprintf('A%d:%s%d',$row,$colMax,$row);
      $data = $ws->rangeToArray($range,null,false,false,false);
      $this->processRow($results,$data[0]);
    }

    /*
    $rows = $ws->toArray();
    foreach($rows as $row) {
      $this->processRow($results,$row);
    }
    */

    $this->addOfficial();

    $results->officials = $this->officials;

    return $results;
  }
}