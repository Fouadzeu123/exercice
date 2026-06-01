import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\NodeController::start
 * @see app/Http/Controllers/NodeController.php:211
 * @route '/generation/start'
 */
export const start = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/generation/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NodeController::start
 * @see app/Http/Controllers/NodeController.php:211
 * @route '/generation/start'
 */
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NodeController::start
 * @see app/Http/Controllers/NodeController.php:211
 * @route '/generation/start'
 */
start.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\NodeController::start
 * @see app/Http/Controllers/NodeController.php:211
 * @route '/generation/start'
 */
    const startForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: start.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\NodeController::start
 * @see app/Http/Controllers/NodeController.php:211
 * @route '/generation/start'
 */
        startForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: start.url(options),
            method: 'post',
        })
    
    start.form = startForm
/**
* @see \App\Http\Controllers\NodeController::claim
 * @see app/Http/Controllers/NodeController.php:264
 * @route '/generation/{id}/claim'
 */
export const claim = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

claim.definition = {
    methods: ["post"],
    url: '/generation/{id}/claim',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NodeController::claim
 * @see app/Http/Controllers/NodeController.php:264
 * @route '/generation/{id}/claim'
 */
claim.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return claim.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NodeController::claim
 * @see app/Http/Controllers/NodeController.php:264
 * @route '/generation/{id}/claim'
 */
claim.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claim.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\NodeController::claim
 * @see app/Http/Controllers/NodeController.php:264
 * @route '/generation/{id}/claim'
 */
    const claimForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: claim.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\NodeController::claim
 * @see app/Http/Controllers/NodeController.php:264
 * @route '/generation/{id}/claim'
 */
        claimForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: claim.url(args, options),
            method: 'post',
        })
    
    claim.form = claimForm
const generation = {
    start: Object.assign(start, start),
claim: Object.assign(claim, claim),
}

export default generation