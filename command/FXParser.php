<?php

require_once './classes/PammRating.php';
require_once './classes/Pamm.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FXParser extends Command
{
	protected function configure()
	{
		$this
			->setName('fxtrend:parser')
			->setDescription('Парсит список ПАММ-счетов с FX-Trend. Добавляет в БД новые')
		;
	}

	/**
	 * Парсит http://fx-trend.com/pamm/rating/
	 * Проходит по страницам и пытается добавить ПАММ-счет в БД, если его там ещё нет.
	 * При добавлении обновляются все показатели.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pammRating = new PammRating();
		$pammRating->getAllPamms();

		//$output->writeln("Список ПАММ-счетов");
	}
}