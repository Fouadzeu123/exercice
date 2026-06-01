import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
export const page = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/generate',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
page.url = (options?: RouteQueryOptions) => {
    return page.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
page.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
page.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
    const pageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: page.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
        pageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: page.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\NodeController::page
 * @see app/Http/Controllers/NodeController.php:43
 * @route '/generate'
 */
        pageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: page.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    page.form = pageForm
const generate = {
    page: Object.assign(page, page),
}

export default generate