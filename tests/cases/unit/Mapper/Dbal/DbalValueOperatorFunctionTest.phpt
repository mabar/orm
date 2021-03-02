<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Orm\Mapper\Dbal;


use Mockery;
use Nextras\Dbal\QueryBuilder\QueryBuilder;
use Nextras\Orm\Collection\Functions\BaseCompareFunction;
use Nextras\Orm\Collection\Functions\CompareEqualsFunction;
use Nextras\Orm\Collection\Functions\CompareNotEqualsFunction;
use Nextras\Orm\Collection\Helpers\DbalExpressionResult;
use Nextras\Orm\Collection\Helpers\DbalQueryBuilderHelper;
use NextrasTests\Orm\TestCase;
use Tester\Assert;


require_once __DIR__ . '/../../../../bootstrap.php';


class DbalValueOperatorFunctionTest extends TestCase
{
	/**
	 * @dataProvider operatorTestProvider
	 * @param array<mixed> $expected
	 * @param array<mixed> $expr
	 */
	public function testOperators(BaseCompareFunction $function, array $expected, array $expr): void
	{
		$expressionResult = new DbalExpressionResult(['%column', 'books.id']);

		$helper = Mockery::mock(DbalQueryBuilderHelper::class);
		$helper->shouldReceive('processPropertyExpr')->once()->andReturnUsing(function ($_, $__, $expressionResultCb) use ($expressionResult) {
			return $expressionResultCb($expressionResult);
		});

		$builder = Mockery::mock(QueryBuilder::class);
		$builder->shouldReceive('getFromAlias')->andReturn('books');

		Assert::same(
			$expected,
			$function->processQueryBuilderExpression($helper, $builder, $expr)->args
		);
	}


	/**
	 * @return array<array{BaseCompareFunction, array<mixed>, array<mixed>}>>
	 */
	protected function operatorTestProvider(): array
	{
		return [
			[new CompareEqualsFunction(), ['%ex = %any', ['%column', 'books.id'], 1], ['id', 1]],
			[new CompareNotEqualsFunction(), ['%ex != %any', ['%column', 'books.id'], 1], ['id', 1]],
			[new CompareEqualsFunction(), ['%ex IN %any', ['%column', 'books.id'], [1, 2]], ['id', [1, 2]]],
			[new CompareNotEqualsFunction(), ['%ex NOT IN %any', ['%column', 'books.id'], [1, 2]], ['id', [1, 2]]],
			[new CompareNotEqualsFunction(), ['%ex IS NOT NULL', ['%column', 'books.id']], ['id', null]],
		];
	}
}


$test = new DbalValueOperatorFunctionTest();
$test->run();
