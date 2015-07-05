<?php

/**
 * This file is part of the Nextras\Orm library.
 *
 * @license    MIT
 * @link       https://github.com/nextras/orm
 * @author     Jan Skrasek
 */

namespace Nextras\Orm\Collection\Helpers;

use Closure;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\InvalidStateException;
use Nextras\Orm\NotSupportedException;
use Nextras\Orm\Relationships\IRelationshipCollection;


class ArrayCollectionClosureHelper
{

	/**
	 * @param  string $condition
	 * @param  mixed  $value
	 * @return Closure
	 */
	public static function createFilter($condition, $value)
	{
		list($chain, $operator) = ConditionParserHelper::parseCondition($condition);

		if ($value instanceof IEntity) {
			$value = $value->getValue('id');
		}

		if ($operator === ConditionParserHelper::OPERATOR_EQUAL) {
			if (is_array($value)) {
				$predicate = function($property) use ($value) {
					return in_array($property, $value, TRUE);
				};
			} else {
				$predicate = function($property) use ($value) {
					return $property === $value;
				};
			}
		} elseif ($operator === ConditionParserHelper::OPERATOR_NOT_EQUAL) {
			if (is_array($value)) {
				$predicate = function($property) use ($value) {
					return !in_array($property, $value, TRUE);
				};
			} else {
				$predicate = function($property) use ($value) {
					return $property !== $value;
				};
			}
		} elseif ($operator === ConditionParserHelper::OPERATOR_GREATER) {
			$predicate = function($property) use ($value) {
				return $property > $value;
			};
		} elseif ($operator === ConditionParserHelper::OPERATOR_EQUAL_OR_GREATER) {
			$predicate = function($property) use ($value) {
				return $property >= $value;
			};
		} elseif ($operator === ConditionParserHelper::OPERATOR_SMALLER) {
			$predicate = function($property) use ($value) {
				return $property < $value;
			};
		} elseif ($operator === ConditionParserHelper::OPERATOR_EQUAL_OR_SMALLER) {
			$predicate = function($property) use ($value) {
				return $property <= $value;
			};
		} else {
			throw new NotSupportedException();
		}

		return static::createFilterEvaluator($chain, $predicate);
	}


	protected static function createFilterEvaluator($chainSource, Closure $predicate)
	{
		$evaluator = function($element, $chain = NULL) use (& $evaluator, $predicate, $chainSource) {
			if (!$chain) {
				$chain = $chainSource;
			}

			$key = array_shift($chain);
			$element = $element->$key;

			if (!$chain) {
				return $predicate($element instanceof IEntity ? $element->id : $element);
			}

			if ($element === NULL) {
				return FALSE;

			} elseif ($element instanceof IRelationshipCollection) {
				foreach ($element as $node) {
					if ($evaluator($node, $chain)) {
						return TRUE;
					}
				}

				return FALSE;
			} else {
				return $evaluator($element, $chain);
			}
		};

		return $evaluator;
	}


	/**
	 * @param  string $condition
	 * @param  string $direction
	 * @return Closure
	 */
	public static function createSorter(array $conditions)
	{
		$columns = [];
		foreach ($conditions as $pair) {
			list($column) = ConditionParserHelper::parseCondition($pair[0]);
			$columns[] = [$column, $pair[1]];
		}

		$getter = function($element, $chain) use (& $getter) {
			$key = array_shift($chain);
			$element = $element->$key;
			if ($element instanceof IRelationshipCollection) {
				throw new InvalidStateException('You can not sort by hasMany relationship.');
			}

			if (!$chain) {
				return $element;
			} else {
				return $getter($element, $chain);
			}
		};

		return function ($a, $b) use ($getter, $columns) {
			foreach ($columns as $pair) {
				$_a = $getter($a, $pair[0]);
				$_a = $_a instanceof IEntity ? $_a->getValue('id') : $_a;
				$_b = $getter($b, $pair[0]);
				$_b = $_b instanceof IEntity ? $_b->getValue('id') : $_b;
				$direction = $pair[1] === ICollection::ASC ? 1 : -1;

				if (is_int($_a) || is_float($_a)) {
					if ($_a < $_b) {
						return $direction * -1;
					} elseif ($_a > $_b) {
						return $direction;
					}
				} else {
					$res = strcmp((string) $_a, (string) $_b);
					if ($res < 0) {
						return $direction * -1;
					} elseif ($res > 0) {
						return $direction;
					}
				}
			}

			return 0;
		};
	}

}
