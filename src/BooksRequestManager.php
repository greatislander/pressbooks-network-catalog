<?php

namespace PressbooksNetworkCatalog;

//use Illuminate\Http\Request;
use Pressbooks\DataCollector\Book;

class BooksRequestManager
{
	private array $filtersConfig;

	//    private Request $request;

	private array $request;

	public function __construct()
	{
		$this->filtersConfig = [
			'subjects' => [
				'column' => Book::SUBJECTS_CODES,
				'alias' => 'subjects',
				'name' => 'subjects',
				'type' => 'array',
				'conditionQueryType' => 'subquery',
			],
			'licenses' => [
				'column' => Book::LICENSE,
				'name' => 'licenses',
				'alias' => 'license',
				'type' => 'array',
				'conditionQueryType' => 'standard',
			],
			'institutions' => [
				'column' => Book::INSTITUTIONS,
				'name' => 'institutions',
				'alias' => 'institutions',
				'type' => 'array',
				'conditionQueryType' => 'subquery',
			],
			'publishers' => [
				'column' => Book::PUBLISHER,
				'name' => 'publishers',
				'alias' => 'publisher',
				'type' => 'array',
				'conditionQueryType' => 'standard',
			],
			'h5p' => [
				'column' => Book::H5P_ACTIVITIES,
				'name' => 'h5p',
				'alias' => 'h5pCount',
				'type' => 'boolean',
				'conditionQueryType' => 'numeric',
			],
			'last_updated' => [
				'column' => Book::LAST_EDITED,
				'name' => 'last_updated',
				'alias' => 'updatedAt',
				'type' => 'string',
				'regex' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
				'conditionQueryType' => 'date',
			],
			'page' => [
				'type' => 'integer',
				'default' => 0,
			],
			'per_page' => [
				'type' => 'integer',
				'default' => 15,
			],
		];

		//        $this->request = Request::capture()->all();
		//        var_dump($this->request->all());

		$this->request = $_GET;
	}

	public function validateRequest(): bool
	{
		foreach ($this->filtersConfig as $filter => $config) {
			if (isset($this->request[$filter]) && ! empty($this->request[$filter])) {
				if (
					gettype($this->request[$filter]) !== $config['type'] ||
					isset($config['regex']) && ! preg_match($config['regex'], $this->request[$filter])
				) {
					return false;
				}
			}
		}

		return true;
	}

	public function getPageLimit(): int
	{
		return $this->request['per_page'] ?? $this->filtersConfig['per_page']['default'];
	}

	public function getPageOffset(): int
	{
		return isset($this->request['page']) ?
			($this->request['page'] - 1) * $this->getPageLimit() : $this->filtersConfig['page']['default'];
	}

	public function getSqlConditions(): string
	{
		if (empty($this->request)) {
			return '';
		}
		$sqlQueryConditions = [];

		global $wpdb;

		foreach ($this->filtersConfig as $filter => $config) {
			if (isset($this->request[$filter]) && ! empty($this->request[$filter])) {
				if (isset($config['conditionQueryType'])) {
					if ($config['conditionQueryType'] === 'standard') {
						$in_placeholder = array_fill(0, count($this->request[$filter]), '%s');
						$sqlQueryConditions[] = $config['alias'].
							$wpdb->prepare(' IN ('.implode(', ', $in_placeholder).')', $this->request[$filter]);
					}
					if ($config['conditionQueryType'] === 'subquery') {
						$in = '';
						foreach ($this->request[$filter] as $filterValue) {
							$in .= $wpdb->prepare('%s', $filterValue).',';
						}
						$in = rtrim($in, ',');
						$column = $config['column'];
						$sqlQueryConditions[] = " blog_id IN (SELECT blog_id FROM {$wpdb->blogmeta}
                            WHERE meta_key = '$column' AND meta_value IN ($in) GROUP BY blog_id)";
					}
					if ($config['conditionQueryType'] === 'date') {
						$column = $config['alias'];
						$sqlQueryConditions[] = "UNIX_TIMESTAMP($column) > UNIX_TIMESTAMP(".
												$wpdb->prepare('%s', $this->request[$filter]).')';
					}
					if ($config['conditionQueryType'] === 'numeric') {
						$column = $config['alias'];
						$sqlQueryConditions[] = $this->request[$filter] ? "$column > 0" : "$column = 0";
					}
				}
			}
		}

		return empty($sqlQueryConditions) ? '' : '  HAVING '.implode(' AND ', $sqlQueryConditions);
	}
}