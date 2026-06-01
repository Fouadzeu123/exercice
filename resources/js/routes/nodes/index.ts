import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/nodes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\NodeController::index
 * @see app/Http/Controllers/NodeController.php:20
 * @route '/nodes'
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
/**
* @see \App\Http\Controllers\NodeController::rent
 * @see app/Http/Controllers/NodeController.php:79
 * @route '/nodes/{id}/rent'
 */
export const rent = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rent.url(args, options),
    method: 'post',
})

rent.definition = {
    methods: ["post"],
    url: '/nodes/{id}/rent',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NodeController::rent
 * @see app/Http/Controllers/NodeController.php:79
 * @route '/nodes/{id}/rent'
 */
rent.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return rent.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NodeController::rent
 * @see app/Http/Controllers/NodeController.php:79
 * @route '/nodes/{id}/rent'
 */
rent.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rent.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\NodeController::rent
 * @see app/Http/Controllers/NodeController.php:79
 * @route '/nodes/{id}/rent'
 */
    const rentForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: rent.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\NodeController::rent
 * @see app/Http/Controllers/NodeController.php:79
 * @route '/nodes/{id}/rent'
 */
        rentForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: rent.url(args, options),
            method: 'post',
        })
    
    rent.form = rentForm
const nodes = {
    index: Object.assign(index, index),
rent: Object.assign(rent, rent),
}

export default nodes