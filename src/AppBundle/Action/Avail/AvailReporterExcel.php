<?php

namespace AppBundle\Action\Avail;

class AvailReporterExcel
{
  protected $wb;

  /* ===================================
   * Main Entry Point
   *
   */
  public function report($dates,$officials)
  {
    $results = new AvailReporterResults();

    // For wrapping text
    \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());

    $this->wb = $wb = new \PHPExcel();
    $ws = $wb->getSheet();
    $ws->setTitle('RefAvail');

    $ws->getCell('A1')->setValue('Referee Name');
    $ws->getCell('B1')->setValue('Referee Info');

    $ws->getColumnDimension('A')->setWidth(20);
    $ws->getColumnDimension('B')->setWidth(20);

    $col = 'C';
    foreach($dates as $date)
    {
      $dt = \DateTime::createFromFormat('Y-m-d',$date);

      $ws->getCell($col . '1')->setValue($dt->format('D M d'));
      $ws->getColumnDimension($col)->setWidth(25);
      $col++;
    }
    $row = 2;
    foreach($officials as $official)
    {
      $ws->getCell('A' . $row)->setValue($official['name']);

      $info = sprintf("F: %s\n%s\n%s\nR: %s",$official['city'],$official['cell'],$official['home'],$official['rank']);
      $ws->getCell('B' . $row)->setValue($info);

      $col = 'C';
      foreach($dates as $date)
      {
        $cr = $col . $row;
        $avail = implode("\n",$official['avail'][$date]);
        $ws->getCell($cr)->setValue($avail);
        if ($avail === 'Blocked ALL DAY') {
          $style = $ws->getStyle($cr);
          $fill = $style->getFill();
          $fill->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
          $fill->getStartColor()->setARGB('FFFF6666');
        }
        if ($avail === 'Open All Day') {
          $style = $ws->getStyle($cr);
          $fill = $style->getFill();
          $fill->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
          $fill->getStartColor()->setARGB('FFCCFFCC');
        }
        $col++;
      }
      $row++;
    }
    $wb->setActiveSheetIndex(0);

    return $results;
  }
  public function getContents()
  {
    ob_start();
    $this->save('php://output');
    return ob_get_clean();
  }
  public function save($filename)
  {
    $writer = \PHPExcel_IOFactory::createWriter($this->wb, "Excel2007");
    $writer->save($filename);
  }
  public function getFileExtension()
  {
    return 'xlsx';
  }
  public function getContentType()
  {
    return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
  }
}