import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const hasRole = (role) => {
        const roles = usePage().props.auth.roles || [];
        return roles.includes(role);
    };

    const hasPermission = (permission) => {
        // Admin always has all permissions
        if (hasRole('admin')) {
            return true;
        }

        const permissions = usePage().props.auth.permissions || [];
        return permissions.includes(permission);
    };

    return {
        hasRole,
        hasPermission,
    };
}
