import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/vip',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\VIPController::index
 * @see app/Http/Controllers/VIPController.php:14
 * @route '/vip'
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
const vip = {
    index: Object.assign(index, index),
}

export default vip