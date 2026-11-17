import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/weather',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WeatherController::show
* @see app/Http/Controllers/WeatherController.php:15
* @route '/api/weather'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/weather',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Web\WeatherController::index
* @see app/Http/Controllers/Web/WeatherController.php:11
* @route '/weather'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const weather = {
    show: Object.assign(show, show),
    index: Object.assign(index, index),
}

export default weather