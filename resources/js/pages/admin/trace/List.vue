<template>
    <AppLayout title="Users">
        <template #header>
            <Button type="button" @click="inviteUser" variant="add" size="sm"><Send class="mr-1" />Invite User</Button>
            <Button type="button" @click="addUser" variant="add" size="sm"><CirclePlus class="mr-1" />Add User</Button>
        </template>
        <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
            <Card class="mb-6 p-4">
                <CardTitle class="text-lg mb-4">Filters & Settings</CardTitle>
                <div class="flex flex-wrap gap-4">
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <Label class="block mb-1">Name</Label>
                            <TextInput
                                v-model="filterForm.name"
                                type="text"
                                placeholder="Name"
                            />
                        </div>
                    </div>
        
                    <div class="ml-auto flex items-end space-x-2">
                        <div>
                            <Label class="block mb-1">per Page</Label>
                            <SelectDropdown
                                v-model="filterForm.perPage"
                                :data="perPageOptions"
                                dataType="object"
                                keyIndex="id"
                                valueIndex="id"
                                labelIndex="name"
                            />
                        </div>
                        <div>
                            <Label class="block mb-1">Sort by</Label>
                            <SelectDropdown
                                v-model="filterForm.sortBy"
                                :data="sortByOptions"
                                dataType="object"
                                keyIndex="id"
                                valueIndex="id"
                                labelIndex="name"
                            />
                        </div>
                        <Button 
                            @click="toggleSortOrder" 
                            variant="outline" 
                            size="sm"
                            :title="filterForm.sortOrder === 'asc' ? 'Ascending' : 'Descending'"
                            class="h-10"
                        >
                            <span class="text-xl font-bold"><MoveUp v-if="filterForm.sortOrder === 'asc'" /><MoveDown v-else /></span>
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Trace Data -->
            <SmartTables
                :url="adminTrace.data.url()"
                :columns="[
                    { key: 'name', label: 'Name', sortable: true, searchable: true },
                    { key: 'email', label: 'Email', sortable: true, searchable: true },
                    { key: 'created_at', label: 'Created', sortable: true, format: (_, v) => new Date(v).toLocaleDateString() },
                ]"
                :per-page="20"
                @row-click="(row) => router.visit(adminUser.edit.url([row.id]))"
                />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { router, useForm, usePage, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import adminUser from '@/routes/admin/user';
import Card from '@/components/ui/card/Card.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Button from '@/components/ui/button/Button.vue';
import TextInput from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import SelectDropdown from '@/components/ui/select/SelectDropdown.vue';
import SmartTables from '@/components/SmartTables.vue';
import { CirclePlus, User, Send, MoveUp, MoveDown } from "lucide-vue-next";
import type { User as UserType } from '@/types';
import invitation from '@/routes/admin/invitation';

// Extend User type for the list items
interface UserListItem extends UserType {
    roles?: Array<{ name: string }>;
    lastlogin_at?: string | null;
}

// Type for DataDisplay component instance
interface DataDisplayInstance {
    refresh: () => void;
}

const props = defineProps({
});

const page = usePage();

// Load saved preferences from localStorage
const savedPreferences = JSON.parse(localStorage.getItem('user-preferences') || '{}');

const filterForm = useForm({
    name: savedPreferences.name || '',
    userMode: savedPreferences.userMode || 'list',
    sortBy: savedPreferences.sortBy || 'name',
    sortOrder: savedPreferences.sortOrder || 'asc',
    perPage: savedPreferences.perPage || 20,
});

const ajaxParams = computed(() => {
    // Changing list/tile mode should NOT trigger a data reload
    const { userMode, ...params } = filterForm.data();
    return params;
});

const perPageOptions = [
    { id: '10', name: '10' },
    { id: '20', name: '20' },
    { id: '50', name: '50' },
    { id: '100', name: '100' }
];
// User data state
//const users = ref([]);
const loading = ref(false);
const error = ref(null);
const totalItems = ref(0);
const currentPage = ref(1);

// Select dropdown data
const sortByOptions = [
    { id: 'name', name: 'Name' },
    { id: 'email', name: 'Email' },
];

// Debug log to see what's loaded
//console.log('Initial userMode:', filterForm.userMode, 'from localStorage:', localStorage.getItem('user-mode'));

const dataUserRef = ref<DataDisplayInstance | null>(null);

// Load user data
const loadUsers = async () => {
    if (dataUserRef.value) {
        dataUserRef.value.refresh();
    }
};

// Handle page change
const handlePageChange = (pageNum: number) => {
    loadUsers();
};

// Handle item click
const handleItemClick = (item: UserListItem) => {
    // Navigate to edit page
    router.visit(adminUser.edit.url([item.id]));
};

// Handle mode change
const handleModeChange = (mode: string) => {
    filterForm.userMode = mode;
};

// Handle data error
const handleDataError = (error: unknown) => {
    console.error('DataDisplay error:', error);
};

// Handle refresh
const handleRefresh = () => {
//    loadUsers();
};

// Toggle sort order
const toggleSortOrder = () => {
    filterForm.sortOrder = filterForm.sortOrder === 'asc' ? 'desc' : 'asc';
    loadUsers();
};

// Add user function
const addUser = () => {
    router.visit(adminUser.new.url());
};

// Invite user function
const inviteUser = () => {
    router.visit(invitation.new.url());
};

// Edit user function
const editUser = (item: UserListItem) => {
    router.visit(adminUser.edit.url([item.id]));
};

// Watch for user filter changes (exclude userMode to avoid reload on list/tile toggle)
watch([() => filterForm.name, () => filterForm.sortBy, () => filterForm.sortOrder, () => filterForm.perPage], () => {
    currentPage.value = 1;
    console.log('watch filterForm.data(): ', filterForm.data());
    if (dataUserRef.value) {
        console.log('watch dataUserRef.value.refresh(): ');
        dataUserRef.value.refresh();
    }
});

// Watch for page changes
watch(currentPage, (newPage) => {
    console.log('watch currentPage: ', newPage);
    if (dataUserRef.value) {
        dataUserRef.value.refresh();
    }
});

// Watch for filterForm changes to persist to localStorage
watch(filterForm, (newForm) => {
    const preferences = {
        name: newForm.name,
        userMode: newForm.userMode,
        sortBy: newForm.sortBy,
        sortOrder: newForm.sortOrder,
        perPage: newForm.perPage,
    };
    localStorage.setItem('user-preferences', JSON.stringify(preferences));
}, { deep: true });
</script>