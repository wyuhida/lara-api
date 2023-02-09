<?php
namespace App\Helper;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

Trait ApiResponser
{
    private function successResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    protected function errorResponse($message, $code)
    {
        return response()->json(['message'=>$message, 'code' => $code], $code);
    }

    protected function showAll(Collection $collection, $code = 200)
    {
        if($collection->isEmpty())
        {
            return $this->successResponse(['data' => $collection, 'code' => $code], $code);
        }
        $transformer = $collection->first()->transformer;
        //filter
        $collection = $this->filterData($collection, $transformer);
        //sort by
        $collection = $this->sortData($collection, $transformer);
        $collection = $this->paginate($collection, $transformer);
        $collection = $this->transformData($collection, $transformer);
        // cache
        $collection = $this->cacheResponse($collection);

        return $this->successResponse($collection, $code);

        //original
        // return $this->successResponse(['data' => $collection, 'code' => $code], $code);

    }

    protected function showOne(Model $instance, $code = 200)
    {
        $tranformer = $instance->transformer;
        $instance = $this->transformData($instance, $tranformer);
        return $this->successResponse($instance, $code);
        //original
        // return $this->successResponse(['data' => $model], $code);
    }

    protected function showMessage($message, $code = 200)
    {
        return $this->successResponse(['data' => $message], $code);
    }

    protected function filterData(Collection $collection, $tranformer)
    {
        foreach(request()->query() as $query => $value)
        {
            $attribute = $tranformer::originalAttribute($query);
            if(isset($attribute, $value))
            {
                $collection = $collection->where($attribute, $value);
            }
        }
        return $collection;
    }

    protected function sortData(Collection $collection, $tranformer)
    {
        if(request()->has('sort_by'))
        {
            $attribute = $tranformer::originalAttribute(request()->sort_by);
            $collection = $collection->sortBy->{$attribute};
        }
        return $collection;
    }

    protected function paginate(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:50',
        ];
        // Validator::validate(request()->all(), $rules);
        Validator::validate(request()->all(),$rules);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        if(request()->has('per_page'))
        {
            $perPage = (int) request()->per_page;
        }

        $result = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        $paginated = new LengthAwarePaginator($result, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
        $paginated->appends(request()->all());
        return $paginated;
    }

    protected function transformData($data, $transformer)
    {
        $transformation = fractal($data, new $transformer);
        return $transformation->toArray(); 
    }

    protected function cacheResponse($data)
    {
        $url = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        $fullUrl = "{$url}?{$queryString}";

        return Cache::remember($fullUrl, 30/60, function () use ($data) {
            return $data;
        });
    }
}