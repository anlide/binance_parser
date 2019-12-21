<?php

namespace App\Command;

use App\Entity\Price;
use Larislackers\BinanceApi\BinanceApiContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DaemonCommand extends Command
{
    const LIMIT_EACH_5000 = 60 * 30;
    const LIMIT_EACH_1000 = 60 * 10;
    const LIMIT_EACH_500 = 60;
    const LIMIT_EACH_100 = 1;
    const SLEEP_MICROTIME = 76923; // NOTE: 1000000 / 13
    const SATOSHY_FACTOR = 100000000;

    protected static $defaultName = 'binance:daemon';

    // TODO: Implement stop uxin-signal handler
    protected static $stopSignal = false;

    /**
     * @var BinanceApiContainer
     */
    protected $bac;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('binance:daemon')
            ->setDescription('Run daemon')
            ->setHelp('This command starts binance-parser daemon');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bac = new BinanceApiContainer($_ENV['BINANCE_KEY'], $_ENV['BINANCE_SECRET']);
        $lastRequest = time();

        do {
            $time = time();
            if ($lastRequest < $time) {
                $this->step($time);
                $lastRequest = $time;
            }

            usleep(self::SLEEP_MICROTIME);
        } while (!self::$stopSignal);
    }

    /**
     * @param $time
     */
    protected function step($time)
    {
        $limit = 100;
        if (0 === $time % self::LIMIT_EACH_500) {
            $limit = 500;
        }
        if (0 === $time % self::LIMIT_EACH_1000) {
            $limit = 1000;
        }
        if (0 === $time % self::LIMIT_EACH_5000) {
            $limit = 5000;
        }

        list($lastUpdateId, $prices) = $this->getPrices($limit);
        if (100 !== $limit) {
            $this->storePrices($lastUpdateId, $time, $prices);
        }
        $this->processAnalytic($lastUpdateId, $prices);
        $this->webSocketBroadcast($prices);
    }

    /**
     * @param int $limit
     * @return array
     */
    protected function getPrices(int $limit)
    {
        $orders = $this->bac->getOrderBook(['symbol' => 'BTCUSDT', 'limit' => $limit]);
        $content = json_decode($orders->getBody()->getContents());

        $lastUpdateId = $content->lastUpdateId;
        $asks = $content->asks;
        $bids = $content->bids;
        $prices = [];

        foreach ($asks as $ask) {
            $price = intval(floatval($ask[0]) * 100);
            $amount = intval(floatval($ask[1]) * self::SATOSHY_FACTOR);
            $prices[$price] = $amount;
        }
        foreach ($bids as $bid) {
            $price = intval(floatval($bid[0]) * 100);
            $amount = intval(floatval($bid[1]) * self::SATOSHY_FACTOR);
            $prices[$price] = $amount;
        }

        return [$lastUpdateId, $prices];
    }

    /**
     * @param int $lastUpdateId
     * @param int $timestamp
     * @param array $prices
     */
    protected function storePrices(int $lastUpdateId, int $timestamp, array $prices)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        try {
            foreach ($prices as $price => $amount) {
                $priceObj = new Price();
                $priceObj->init($timestamp, $price, $amount);
                $em->persist($priceObj);
            }
            $em->flush(); //Persist objects that did not make up an entire batch
            $em->clear();
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $exception) {
            error_log('Stop saving: '.$exception->getMessage());
            exit;
        } catch (\Doctrine\ORM\ORMException $exception) {
            error_log('Stop saving: '.$exception->getMessage());
            exit;
        }
    }

    /**
     * @param int $lastUpdateId
     * @param array $prices
     */
    protected function processAnalytic(int $lastUpdateId, array $prices)
    {
        // TODO: Try to find all walls
        // TODO: Try to find all clouds
    }

    /**
     * @param array $prices
     */
    protected function webSocketBroadcast(array $prices)
    {
        $groupPriceBy100 = $this->groupPrices(100, $prices);

        $satoshyPrices = [];
        foreach ($groupPriceBy100 as $price => $amount) {
            $satoshyPrices[$price] = $amount / self::SATOSHY_FACTOR;
        }

        //var_dump($satoshyPrices);
        // TODO: Broadcast via websocket $groupPriceBy100
    }

    /**
     * @param int $factor
     * @param array $prices
     *
     * @return array
     */
    protected function groupPrices(int $factor, array $prices)
    {
        $newPrices = [];
        foreach ($prices as $price => $amount) {
            // NOTE: it is critical to use "floor" function to round prices
            $price = intval(floor($price / $factor));
            if (!isset($newPrices[$price])) {
                $newPrices[$price] = 0;
            }

            $newPrices[$price] += $amount;
        }

        return $newPrices;
    }
}
