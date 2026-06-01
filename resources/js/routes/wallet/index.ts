import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\WalletController::index
 * @see app/Http/Controllers/WalletController.php:13
 * @route '/wallet'
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
* @see \App\Http\Controllers\WalletController::deposit
 * @see app/Http/Controllers/WalletController.php:45
 * @route '/wallet/deposit'
 */
export const deposit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

deposit.definition = {
    methods: ["post"],
    url: '/wallet/deposit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WalletController::deposit
 * @see app/Http/Controllers/WalletController.php:45
 * @route '/wallet/deposit'
 */
deposit.url = (options?: RouteQueryOptions) => {
    return deposit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WalletController::deposit
 * @see app/Http/Controllers/WalletController.php:45
 * @route '/wallet/deposit'
 */
deposit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deposit.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\WalletController::deposit
 * @see app/Http/Controllers/WalletController.php:45
 * @route '/wallet/deposit'
 */
    const depositForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: deposit.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\WalletController::deposit
 * @see app/Http/Controllers/WalletController.php:45
 * @route '/wallet/deposit'
 */
        depositForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: deposit.url(options),
            method: 'post',
        })
    
    deposit.form = depositForm
/**
* @see \App\Http\Controllers\WalletController::withdraw
 * @see app/Http/Controllers/WalletController.php:82
 * @route '/wallet/withdraw'
 */
export const withdraw = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(options),
    method: 'post',
})

withdraw.definition = {
    methods: ["post"],
    url: '/wallet/withdraw',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WalletController::withdraw
 * @see app/Http/Controllers/WalletController.php:82
 * @route '/wallet/withdraw'
 */
withdraw.url = (options?: RouteQueryOptions) => {
    return withdraw.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WalletController::withdraw
 * @see app/Http/Controllers/WalletController.php:82
 * @route '/wallet/withdraw'
 */
withdraw.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: withdraw.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\WalletController::withdraw
 * @see app/Http/Controllers/WalletController.php:82
 * @route '/wallet/withdraw'
 */
    const withdrawForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: withdraw.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\WalletController::withdraw
 * @see app/Http/Controllers/WalletController.php:82
 * @route '/wallet/withdraw'
 */
        withdrawForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: withdraw.url(options),
            method: 'post',
        })
    
    withdraw.form = withdrawForm
const wallet = {
    index: Object.assign(index, index),
deposit: Object.assign(deposit, deposit),
withdraw: Object.assign(withdraw, withdraw),
}

export default wallet