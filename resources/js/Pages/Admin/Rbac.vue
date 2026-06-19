<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { ref, computed } from 'vue'

const props = defineProps<{
    roles: Array<{
        id: number
        name: string
        permissions: Array<{ name: string }>
    }>
    permissions: Record<string, Array<{ name: string }>>
    users: Array<{
        id: number
        name: string
        email: string
        roles: Array<{ name: string }>
    }>
    permissionModules: string[]
}>()

const showCreateRole = ref(false)
const newRoleName = ref('')
const selectedPermissions = ref<string[]>([])
const editingPermissions = ref<Record<number, string[]>>({})
const editingUserRoles = ref<Record<number, string[]>>({})

const userRoleMap = computed(() => {
    const map: Record<number, string[]> = {}
    props.users.forEach(user => {
        map[user.id] = user.roles.map(r => r.name)
    })
    return map
})

const toggleUserRole = (userId: number, roleName: string) => {
    const current = editingUserRoles.value[userId] || [...userRoleMap.value[userId]]
    if (current.includes(roleName)) {
        editingUserRoles.value[userId] = current.filter(r => r !== roleName)
    } else {
        editingUserRoles.value[userId] = [...current, roleName]
    }
}

const updateRolePermissions = (roleId: number, permissions: string[]) => {
    router.put(`/admin/rbac/roles/${roleId}/permissions`, { permissions }, {
        preserveScroll: true,
        onSuccess: () => {
            delete editingPermissions.value[roleId]
        }
    })
}

const updateUserRoles = (userId: number, roles: string[]) => {
    router.put(`/admin/rbac/users/${userId}/roles`, { roles }, {
        preserveScroll: true,
    })
}

const createRole = () => {
    router.post('/admin/rbac/roles', {
        name: newRoleName.value,
        permissions: selectedPermissions.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateRole.value = false
            newRoleName.value = ''
            selectedPermissions.value = []
        }
    })
}

const togglePermission = (permissionName: string, roleId?: number) => {
    if (roleId !== undefined) {
        const current = editingPermissions.value[roleId] || []
        if (current.includes(permissionName)) {
            editingPermissions.value[roleId] = current.filter(p => p !== permissionName)
        } else {
            editingPermissions.value[roleId] = [...current, permissionName]
        }
    } else {
        if (selectedPermissions.value.includes(permissionName)) {
            selectedPermissions.value = selectedPermissions.value.filter(p => p !== permissionName)
        } else {
            selectedPermissions.value = [...selectedPermissions.value, permissionName]
        }
    }
}

const isPermissionSelected = (permissionName: string, roleId?: number): boolean => {
    if (roleId !== undefined) {
        const perms = editingPermissions.value[roleId]
        if (perms) return perms.includes(permissionName)
        const role = props.roles.find(r => r.id === roleId)
        return role?.permissions.some(p => p.name === permissionName) ?? false
    }
    return selectedPermissions.value.includes(permissionName)
}

const getModulePermissions = (module: string): string[] => {
    return (props.permissions[module] || []).map(p => p.name)
}
</script>

<template>
    <AppLayout>
        <Head title="Role Management" />

        <div class="max-w-7xl mx-auto py-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Role-Based Access Control</h1>
                    <p class="text-gray-500">Manage roles and their associated permissions</p>
                </div>
                <Button @click="showCreateRole = true">Create Role</Button>
            </div>

            <!-- Create Role Dialog -->
            <Card v-if="showCreateRole">
                <CardHeader>
                    <CardTitle>Create New Role</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Label for="role-name">Role Name</Label>
                        <Input id="role-name" v-model="newRoleName" placeholder="Enter role name" />
                    </div>
                    <div class="space-y-3">
                        <Label>Permissions by Module</Label>
                        <div v-for="module in permissionModules" :key="module" class="border rounded-lg p-3">
                            <div class="flex flex-wrap gap-2">
                                <Badge 
                                    v-for="perm in permissions[module]" 
                                    :key="perm.name"
                                    :variant="isPermissionSelected(perm.name) ? 'default' : 'outline'"
                                    class="text-xs cursor-pointer"
                                    @click="togglePermission(perm.name)"
                                >
                                    {{ perm.name }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button @click="createRole" :disabled="!newRoleName.trim()">Create</Button>
                        <Button variant="outline" @click="showCreateRole = false">Cancel</Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Roles Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Roles & Permissions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="role in roles" :key="role.id" class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold">{{ role.name }}</h3>
                                <Badge>{{ role.permissions.length }} permissions</Badge>
                            </div>
                            <div class="flex flex-wrap gap-1 mb-3 max-h-20 overflow-y-auto">
                                <Badge 
                                    v-for="perm in role.permissions" 
                                    :key="perm.name" 
                                    variant="secondary" 
                                    class="text-xs"
                                >
                                    {{ perm.name }}
                                </Badge>
                            </div>
                            <div v-if="!editingPermissions[role.id]" class="flex gap-2">
                                <Button size="sm" variant="outline" @click="editingPermissions[role.id] = [...role.permissions.map(p => p.name)]">
                                    Edit Permissions
                                </Button>
                            </div>
                            
                            <!-- Inline permission editor -->
                            <div v-if="editingPermissions[role.id]" class="mt-3 space-y-2">
                                <Label class="text-xs font-medium">Select Permissions</Label>
                                <div v-for="module in permissionModules" :key="module" class="flex flex-wrap gap-2">
                                    <Badge 
                                        v-for="perm in permissions[module]" 
                                        :key="perm.name"
                                        :variant="isPermissionSelected(perm.name, role.id) ? 'default' : 'outline'"
                                        class="text-xs cursor-pointer"
                                        @click="togglePermission(perm.name, role.id)"
                                    >
                                        {{ perm.name }}
                                    </Badge>
                                </div>
                                <div class="flex gap-2">
                                    <Button size="sm" @click="updateRolePermissions(role.id, editingPermissions[role.id] || [])">Save</Button>
                                    <Button size="sm" variant="outline" @click="delete editingPermissions[role.id]">Cancel</Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Section -->
            <Card>
                <CardHeader>
                    <CardTitle>Users & Roles</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <div v-for="user in users" :key="user.id" class="border rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium">{{ user.name }}</p>
                                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                                </div>
                                <Button 
                                    v-if="!editingUserRoles[user.id]" 
                                    size="sm" 
                                    variant="outline"
                                    @click="editingUserRoles[user.id] = [...userRoleMap[user.id]]"
                                >
                                    Edit Roles
                                </Button>
                            </div>
                            
                            <div v-if="editingUserRoles[user.id]" class="space-y-2">
                                <Label class="text-xs font-medium">Select Roles</Label>
                                <div class="flex flex-wrap gap-2">
                                    <Badge 
                                        v-for="role in roles" 
                                        :key="role.id"
                                        :variant="editingUserRoles[user.id]?.includes(role.name) ? 'default' : 'outline'"
                                        class="text-xs cursor-pointer"
                                        @click="toggleUserRole(user.id, role.name)"
                                    >
                                        {{ role.name }}
                                    </Badge>
                                </div>
                                <div class="flex gap-2">
                                    <Button size="sm" @click="updateUserRoles(user.id, editingUserRoles[user.id] || [])">Save</Button>
                                    <Button size="sm" variant="outline" @click="delete editingUserRoles[user.id]">Cancel</Button>
                                </div>
                            </div>
                            
                            <div v-else class="flex flex-wrap gap-1">
                                <Badge 
                                    v-for="roleName in userRoleMap[user.id]" 
                                    :key="roleName" 
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    {{ roleName }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>