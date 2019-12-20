<?php

namespace App\Command;

use Larislackers\BinanceApi\BinanceApiContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommand extends Command
{
    const LIMIT_EACH_5000 = 60 * 20;
    const LIMIT_EACH_1000 = 60 * 5;
    const LIMIT_EACH_500 = 60;
    const LIMIT_EACH_100 = 1;
    const SLEEP_MICROTIME = 76923;
    const SATOSHY_FACTOR = 100000000;

    protected static $defaultName = 'binance:daemon';

    // TODO: Implement stop uxin-signal handler
    protected static $stopSignal = false;

    /**
     * @var BinanceApiContainer
     */
    protected $bac;

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
            // NOTE: limit 5000 - each 20 minutes
            // NOTE: limit 1000 - each 5 minutes
            // NOTE: limit 500 - each minute
            // NOTE: limit 100 - each second
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
        $this->storePrices($lastUpdateId, $prices);
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
            $prices[] = [
                'price' => $price,
                'amount' => $amount,
            ];
        }
        foreach ($bids as $bid) {
            $price = intval(floatval($bid[0]) * 100);
            $amount = intval(floatval($bid[1]) * self::SATOSHY_FACTOR);
            $prices[] = [
                'price' => $price,
                'amount' => $amount,
            ];
        }

        return [$lastUpdateId, $prices];
    }

    /**
     * @param int $lastUpdateId
     * @param array $prices
     */
    protected function storePrices(int $lastUpdateId, array $prices)
    {
        // TODO: Store the data to the database
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
        foreach ($prices as $item) {
            // NOTE: it is critical to use "floor" function to round prices
            $price = intval(floor($item['price'] / $factor));
            if (!isset($newPrices[$price])) {
                $newPrices[$price] = 0;
            }

            $newPrices[$price] += $item['amount'];
        }

        return $newPrices;
    }
}
