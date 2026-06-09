import {
    queryParams,
    type RouteDefinition,
    type RouteFormDefinition,
    type RouteQueryOptions,
} from '@/wayfinder';

type MutationMethod = 'patch' | 'put' | 'delete';
type MutationRoute<TMethod extends MutationMethod> = ((
    options?: RouteQueryOptions,
) => RouteDefinition<TMethod>) & {
    definition: RouteDefinition<[TMethod]>;
    url: (options?: RouteQueryOptions) => string;
    form: (options?: RouteQueryOptions) => RouteFormDefinition<TMethod>;
};

const makeMutationRoute = <TMethod extends 'patch' | 'put' | 'delete'>(
    url: string,
    method: TMethod,
) => {
    const route = ((options?: RouteQueryOptions): RouteDefinition<TMethod> => ({
        url: route.url(options),
        method,
    }) as unknown as RouteDefinition<TMethod>) as MutationRoute<TMethod>;

    route.definition = {
        methods: [method],
        url,
    } as unknown as RouteDefinition<[TMethod]>;

    route.url = (options?: RouteQueryOptions) => route.definition.url + queryParams(options);

    route.form = (options?: RouteQueryOptions): RouteFormDefinition<TMethod> => ({
        action: route.url(options),
        method,
    });

    return route;
};

export const ProfileController = {
    update: makeMutationRoute('/settings/profile', 'patch'),
    destroy: makeMutationRoute('/settings/profile', 'delete'),
};

export const SecurityController = {
    update: makeMutationRoute('/settings/security', 'put'),
};
