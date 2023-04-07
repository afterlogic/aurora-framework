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

        $whereParams = ['Closure|string|array|\Illuminate\Database\Query\Expression $column', 'mixed $operator = null', 'mixed $value = null', 'string $boolean = \'and\''];
        $command->setMethod('where', '\\' . \Illuminate\Database\Eloquent\Builder::class . '|\\' . get_class($model), $whereParams);
        $command->setMethod('firstWhere', '\\' . \Illuminate\Database\Eloquent\Builder::class . '|\\' . get_class($model), $whereParams);

        $whereInParams = ['string $column', 'mixed $values', 'string $boolean = \'and\'', 'bool $not = false'];
        $command->setMethod('whereIn', '\\' . \Illuminate\Database\Eloquent\Builder::class . '|\\' . get_class($model), $whereInParams);

        $command->setMethod('find', '\\' . \Illuminate\Database\Eloquent\Builder::class . '|\\' . get_class($model), ['int|string $id', 'array|string $columns = [\'*\']']);
    }
}