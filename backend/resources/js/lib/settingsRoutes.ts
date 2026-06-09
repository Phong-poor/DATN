import { queryParams } from '@/wayfinder';
import type { RouteDefinition, RouteQueryOptions } from '@/wayfinder';

const makeEditRoute = (url: string) => {
    const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
        url: edit.url(options),
        method: 'get',
    });

    edit.definition = {
        methods: ['get', 'head'],
        url,
    } satisfies RouteDefinition<['get', 'head']>;

    edit.url = (options?: RouteQueryOptions) => edit.definition.url + queryParams(options);

    edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
        url: edit.url(options),
        method: 'get',
    });

    edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
        url: edit.url(options),
        method: 'head',
    });

    return edit;
};

export const editProfile = makeEditRoute('/settings/profile');
export const editSecurity = makeEditRoute('/settings/security');
export const editAppearance = makeEditRoute('/settings/appearance');
