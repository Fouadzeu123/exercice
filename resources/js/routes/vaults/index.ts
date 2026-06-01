import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/vaults',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\VaultController::index
 * @see app/Http/Controllers/VaultController.php:14
 * @route '/vaults'
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
* @see \App\Http\Controllers\VaultController::invest
 * @see app/Http/Controllers/VaultController.php:29
 * @route '/vaults/{id}/invest'
 */
export const invest = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invest.url(args, options),
    method: 'post',
})

invest.definition = {
    methods: ["post"],
    url: '/vaults/{id}/invest',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VaultController::invest
 * @see app/Http/Controllers/VaultController.php:29
 * @route '/vaults/{id}/invest'
 */
invest.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return invest.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VaultController::invest
 * @see app/Http/Controllers/VaultController.php:29
 * @route '/vaults/{id}/invest'
 */
invest.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invest.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\VaultController::invest
 * @see app/Http/Controllers/VaultController.php:29
 * @route '/vaults/{id}/invest'
 */
    const investForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: invest.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\VaultController::invest
 * @see app/Http/Controllers/VaultController.php:29
 * @route '/vaults/{id}/invest'
 */
        investForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: invest.url(args, options),
            method: 'post',
        })
    
    invest.form = investForm
const vaults = {
    index: Object.assign(index, index),
invest: Object.assign(invest, invest),
}

export default vaults