<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts\V1;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class IndexController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $posts = QueryBuilder::for(Post::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::partial('title'),
            ])
            ->allowedSorts(['created_at', 'title'])
            ->simplePaginate(perPage: 15);

        return PostResource::collection($posts);
    }
}
