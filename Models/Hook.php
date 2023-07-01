<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model as ClassesModel;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

class Hook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        if (! $model instanceof ClassesModel) {
            return;
        }

        $modelClass = '\\' . \Illuminate\Database\Eloquent\Builder::class . '|\\' . get_class($model);
        $whereParams = ['Closure|string|array|\Illuminate\Database\Query\Expression $column', 'mixed $operator = null', 'mixed $value = null', 'string $boolean = \'and\''];
        $command->setMethod('where', $modelClass, $whereParams);
        $command->setMethod('firstWhere', $modelClass, $whereParams);

        $whereInParams = ['string $column', 'mixed $values', 'string $boolean = \'and\'', 'bool $not = false'];
        $command->setMethod('whereIn', $modelClass, $whereInParams);

        $command->setMethod('find', $modelClass, ['int|string $id', 'array|string $columns = [\'*\']']);
        $command->setMethod('findOrFail', $modelClass, ['int|string $id', 'mixed $id', 'Closure|array|string $columns = [\'*\']', 'Closure $callback = null']);

        $command->setMethod('first', $modelClass, ['array|string $columns = [\'*\']']);

        $command->setMethod('count', 'int', ['string $columns = \'*\'']);
    }
}
