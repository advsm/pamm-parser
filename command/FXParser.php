<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Zend\Http\Client;
use Zend\Uri\Uri;


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
		$request = new Zend\Http\Request();
		$request->setUri("http://fx-trend.com/pamm/rating/");

		$client = new Client();
		$response = $client->send($request);

		$body = $response->getBody();

		$dom = new Zend\Dom\Query($body);
		$elements = $dom->execute("table.my_accounts_table tr");

		$needToSkip = 2;
		foreach ($elements as $element) {
			// Пропускаем первые 2 строки, поскольку в них не список счетов, а служебная информация
			if ($needToSkip) {
				$needToSkip--;
				continue;
			}

			/**
			 * @var DOMElement $element
			 */

			var_dump($element->nodeValue);
		}



		$output->writeln("Список ПАММ-счетов");
	}
}