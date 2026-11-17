import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/api/weather',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::search
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
searchForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

search.form = searchForm

const WeatherController = { search }

export default WeatherController