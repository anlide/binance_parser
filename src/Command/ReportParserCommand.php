<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\OrderAction;
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
            if (is_dir($this->path.'/'.$file)) {
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
        foreach ($spreadSheet->getAllSheets() as $workSheet) {
            $this->parseSheet($workSheet);
        }
    }

    /**
     * @param Worksheet $workSheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function parseSheet(Worksheet $workSheet): void {
        $index = 2;
        $sumP = 0;
        $sumN = 0;
        do {
            $total = '';
            $pair = '';
            $firstDate = null;
            $ret = $this->readBlock($workSheet, $index, $pair, $total, $firstDate);
            if ($total === null) {
                continue;
            }
            $firstDateTime = new \DateTime();
            $firstDateTime->setTimestamp(strtotime($firstDate));
            $total = $this->getPriceValue($total);
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
                    $orderObj = new Order();
                    $orderObj->init($pairObj->getId(), $total, $firstDateTime);
                    $this->em->persist($orderObj);
                    $this->em->flush();
                }

                // --

                $repoOrderAction = $this->em->getRepository('App:OrderAction');

                foreach ($ret as $actionRow) {
                    $orderActionDateTime = new \DateTime();
                    $orderActionDateTime->setTimestamp(strtotime($actionRow['date']));

                    $orderActionObj = $repoOrderAction->findOneBy(['orderId' => $orderObj->getId(), 'time' => $orderActionDateTime]);
                    if (!$orderActionObj) {
                        $orderActionObj = new OrderAction();
                        $orderActionObj->init(
                            $orderObj->getId(),
                            $this->getIndex($orderObj->getId()),
                            $actionRow['action'] == 'Bought',
                            $actionRow['quantity'],
                            $this->getPriceValue($actionRow['price']),
                            $this->getPriceValue($actionRow['amount']),
                            $orderActionDateTime
                        );
                        $this->em->persist($orderActionObj);
                        $this->em->flush();
                    }
                }
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

    /**
     * @param int $orderId
     * @return int
     */
    private function getIndex(int $orderId): int {
        $repoOrderAction = $this->em->getRepository('App:OrderAction');

        $orderActionObjects = $repoOrderAction->findBy(['orderId' => $orderId]);

        $maxIndex = null;

        foreach ($orderActionObjects as $orderActionObject) {
            if ($maxIndex === null) {
                $maxIndex = $orderActionObject->getIndex();
            } elseif ($maxIndex < $orderActionObject->getIndex()) {
                $maxIndex = $orderActionObject->getIndex();
            }
        }

        return $maxIndex === null ? 0 : ($maxIndex + 1);
    }

    /**
     * @param string $value
     * @return int
     */
    private function getPriceValue(string $value): int {
        return intval(floatval(str_replace(['₮', '$'], '', $value)) * 100);
    }
}
