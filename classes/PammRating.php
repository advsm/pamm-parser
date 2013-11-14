<?php

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Uri\Uri;
use Zend\Dom\Query;

/**
 * Класс управляет парсингом таблицы рейтинга ПАММ-счетов.
 */
class PammRating
{
	const URI_RATING   = "http://fx-trend.com/pamm/rating/";
	const CSS_SELECTOR = "table.my_accounts_table[1] tr";

	/**
	 * Заходит на страницу рейтинга ПАММ-счетов и получает всю информацию о них.
	 */
	public function getAllPamms()
	{
		$request = new Request();
		$request->setUri(self::URI_RATING);

		$client = new Client();
		$response = $client->send($request);

		$body = $response->getBody();

		$dom = new Query($body);
		$elements = $dom->execute(self::CSS_SELECTOR);

		$needToParse = false;
		foreach ($elements as $element) {
			// Пропускаем первую таблицу, начинаем парсить только после того, как увидим заголовок второй таблицы
			if (strlen($element->nodeValue) > 270) {
				if ($needToParse) {
					break;
				}

				$needToParse = true;
				continue;
			}

			if (!$needToParse) {
				continue;
			}

			// Убираем строки с заголовками
			if (strlen($element->nodeValue) > 250) {
				continue;
			}

			// Убираем пустые tr'ы
			if (!$element->nodeValue) {
				continue;
			}

			$pamm = Pamm::fromRatingTable($element);
			$pamm->getSpecifics();
		}
	}
}