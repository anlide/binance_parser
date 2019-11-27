<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Pair;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportParserCommand extends Command
{
  protected static $defaultName = 'quantfury:parse';

  private $path = 'var/reports';

  private $em;

  public function __construct(EntityManagerInterface $em)
  {
    parent::__construct();
    $this->em = $em;
  }

  protected function configure()
  {
    $this->setName('quantfury:parse')
      ->setDescription('Parse report from quantfury');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $files = scandir($this->path);
    foreach ($files as $file) {
      if (in_array($file, ['.', '..'])) {
        continue;
      }
      try {
        $this->parseReport($file);
        var_dump('----------------');
      } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
        var_dump($exception->getMessage());
      } catch (\PhpOffice\PhpSpreadsheet\Exception $exception) {
        var_dump($exception->getMessage());
      }
    }
  }

  /**
   * @param string $file
   * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  private function parseReport(string $file)
  {
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xls");
    $reader->setReadDataOnly(true);
    $spreadSheet = $reader->load($this->path.'/'.$file);
    $workSheet = $spreadSheet->getActiveSheet();
    $index = 2;
    $sumP = 0;
    $sumN = 0;
    do {
      $total = '';
      $pair = '';
      $firstDate = null;
      $ret = $this->readBlock($workSheet, $index, $pair, $total, $firstDate);
      $firstDateTime = new \DateTime();
      $firstDateTime->setTimestamp(strtotime($firstDate));
      $total = floatval(str_replace(['₮', '$'], '', $total)) * 100;
      if ($total > 0) {
        $sumP += $total;
      } else {
        $sumN += $total;
      }

      if (!empty($pair)) {
        $repo = $this->em->getRepository('App:Pair');

        $pairObj = $repo->findOneBy(['name' => $pair]);
        if (!$pairObj) {
          $pairObj = new Pair();
          $pairObj->init($pair);
          $this->em->persist($pairObj);
          $this->em->flush();
        }

        // --

        $repoOrder = $this->em->getRepository('App:Order');
        $orderObj = $repoOrder->findOneBy(['firstDate' => $firstDateTime]);
        if (!$orderObj) {
          $newOrder = new Order();
          $newOrder->init($pairObj->getId(), $total, $firstDateTime);
          $this->em->persist($newOrder);
          $this->em->flush();
        }

        // --

        $repoOrderAction = $this->em->getRepository('App:OrderAction');
      }
    } while (!empty($ret));
  }

  /**
   * @param Worksheet $workSheet
   * @param int $index
   * @param string $pair
   * @param string $total
   * @param string $firstDate
   * @return array|null
   * @throws \PhpOffice\PhpSpreadsheet\Exception
   */
  private function readBlock(Worksheet $workSheet, int &$index, string &$pair, string &$total, string &$firstDate = null)
  {
    $ret = [];
    do {
      $cell = $workSheet->getCell('A'.$index);
      $index++;
      $value = $cell->getValue();
      if (empty($value)) {
        break;
      }
      $ret[] = [
        'action' => $workSheet->getCell('B'.($index-1))->getValue(),
        'quantity' => $workSheet->getCell('C'.($index-1))->getValue(),
        'price' => $workSheet->getCell('D'.($index-1))->getValue(),
        'amount' => $workSheet->getCell('E'.($index-1))->getValue(),
        'date' => $workSheet->getCell('F'.($index-1))->getValue(),
      ];
      if ($firstDate === null) {
        $firstDate = $workSheet->getCell('F'.($index-1))->getValue();
      }
      $pair = $value;
      $total = $workSheet->getCell('G'.($index-1))->getValue();
    } while (true);

    return $ret;
  }
}
