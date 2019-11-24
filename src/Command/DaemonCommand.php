<?php

namespace App\Command;

use Larislackers\BinanceApi\BinanceApiContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DaemonCommand extends Command
{
  protected static $defaultName = 'binance:daemon';

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

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->bac = new BinanceApiContainer($_ENV['BINANCE_KEY'], $_ENV['BINANCE_SECRET']);

    do {
      if (!$this->step()) break;
    } while (self::$stopSignal);
  }

  protected function step()
  {
    $orders = $this->bac->getOrderBook(['symbol' => 'BTCUSDT', 'limit' => 5]);
    var_dump(json_decode($orders->getBody()->getContents()));
  }
}
