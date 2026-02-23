<script setup>
    import { useForm, usePage, router, Head } from "@inertiajs/vue3";
    import { onMounted, ref, computed, watch } from "vue";
    import AppLayout from '@/layouts/AppLayout.vue';
    import adminRole from '@/routes/admin/role';
    import Button from '@/components/ui/button/Button.vue';
    import InputError from '@/components/InputError.vue';
    import InputLabel from '@/components/ui/label/Label.vue';
    import TextInput from '@/components/ui/input/Input.vue';
    import Card from "@/components/ui/card/Card.vue";
    import CardTitle from "@/components/ui/card/CardTitle.vue";
    import DeleteModal from "@/components/DeleteModal.vue";
    import { Save, Trash2, SkipBack, Plus, X } from "lucide-vue-next";
    import SmartSelect from '@/components/ui/select/SmartSelect.vue';
    import axios from 'axios';
    import adminSelect from '@/routes/admin/select';
    
    const props = defineProps({
        role: Object,
        availablePermissions: Object,
        permissionStrings: Object,
    });
    
    const page = usePage();
    const roleForm = useForm({
        id: null,
        name: "",
        slug: "",
        permissions: [],
        company_id: null,
        company: null,  // For Multiselect display
    });
    
    // Group permissions by type -> category
    // Supports both:
    // - flat: { "user.view": "View Users", ... }
    // - typed: { admin: { "user.view": "View Users" }, company: { ... }, ... }
    const permissionGroupsByType = computed(() => {
        const available = props.availablePermissions || {};
        const entries = Object.entries(available);
        if (entries.length === 0) return {};

        const isFlat = entries.every(([, v]) => typeof v === 'string');
        const typed = isFlat ? { all: available } : available;

        const out = {};
        Object.entries(typed).forEach(([type, permissionMap]) => {
            const groups = {};

            Object.entries(permissionMap || {}).forEach(([slug, name]) => {
                const category = slug.split('.')[0];
                if (!groups[category]) {
                    groups[category] = [];
                }
                groups[category].push({ slug, name });
            });

            out[type] = groups;
        });

        return out;
    });

    const permissionTypeOrder = ['admin', 'company', 'contractor', 'client', 'all'];
    const permissionTypes = computed(() => {
        const keys = Object.keys(permissionGroupsByType.value || {});
        const ordered = permissionTypeOrder.filter((k) => keys.includes(k));
        const rest = keys.filter((k) => !permissionTypeOrder.includes(k)).sort();
        return [...ordered, ...rest];
    });

    const typeLabel = (type) => {
        const labels = {
            admin: 'Admin',
            company: 'Company',
            contractor: 'Contractor',
            client: 'Client',
            all: 'Permissions',
        };
        return labels[type] || String(type);
    };

    const permissionValue = (type, slug) => `${type}.${slug}`;

    function slugify(str) {
        if (!str || typeof str !== 'string') return '';
        return str
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    const activePermissionType = ref(null);
    watch(
        permissionTypes,
        (types) => {
            if (!types || types.length === 0) {
                activePermissionType.value = null;
                return;
            }

            if (!activePermissionType.value || !types.includes(activePermissionType.value)) {
                activePermissionType.value = types[0];
            }
        },
        { immediate: true }
    );

    const setActivePermissionType = (type) => {
        activePermissionType.value = type;
    };
    
    // Toggle permission
    const togglePermission = (typedPermissionSlug, legacyPermissionSlug = null) => {
        const remove = (slug) => {
            if (!slug) return;
            const index = roleForm.permissions.indexOf(slug);
            if (index > -1) {
                roleForm.permissions.splice(index, 1);
            }
        };

        const isSelected =
            roleForm.permissions.includes(typedPermissionSlug) ||
            (legacyPermissionSlug ? roleForm.permissions.includes(legacyPermissionSlug) : false);

        if (isSelected) {
            remove(typedPermissionSlug);
            remove(legacyPermissionSlug);
        } else {
            // Prefer typed permissions going forward.
            remove(legacyPermissionSlug);
            roleForm.permissions.push(typedPermissionSlug);
        }
    };
    
    // Company search
    const isCompanyLoading = ref(false);
    const companyOptions = ref([]);
    
    function companySearch(search) {
        isCompanyLoading.value = true;
        axios.get(adminSelect.company.url() + '?search=' + search.toString())
            .then(response => {
                if (response.data) {
                    companyOptions.value = response.data;
                }
                isCompanyLoading.value = false;
            })
            .catch(error => {
                console.error('Company search error:', error);
                isCompanyLoading.value = false;
            });
    }
    
    // Load companies and role data on mount
    onMounted(() => {
        if (props.role) {
            Object.assign(roleForm, props.role);
            // Ensure permissions is an array
            if (!roleForm.permissions) {
                roleForm.permissions = [];
            }
            // Set company object for Multiselect if company_id exists
            if (props.role.company) {
                roleForm.company = props.role.company;
            }
            // Infer permission type from slug prefix (e.g. "company-manager" -> company)
            if (props.role.slug && permissionTypes.value.length) {
                const prefix = props.role.slug.split('-')[0];
                if (permissionTypes.value.includes(prefix)) {
                    activePermissionType.value = prefix;
                }
            }
        }
        companySearch('');
    });
    
    // Watch company selection to update company_id
    watch(() => roleForm.company, (newCompany) => {
        roleForm.company_id = newCompany ? newCompany.id : null;
    });

    // Auto-generate slug from permission type + slugified name: <type>-<name-slug>
    watch(
        () => [roleForm.name, activePermissionType.value],
        () => {
            const typeSlug = activePermissionType.value || 'all';
            const nameSlug = slugify(roleForm.name);
            roleForm.slug = nameSlug ? `${typeSlug}-${nameSlug}` : typeSlug;
        },
        { immediate: true }
    );
    
    const submitForm = () => (roleForm.id ? updateRole() : addRole());
    const updateRole = () => roleForm.put(adminRole.update.url([props.role.id]));
    const addRole= () => roleForm.post(adminRole.create.url());
    const deleteRole = () => roleForm.delete(adminRole.delete.url([props.role.id]));
    const cancelRole = () => {
        router.visit(adminRole.list.url());
    }
    
    const showConfirmDelete = ref(false);
    function openConfirmDelete() {
        showConfirmDelete.value = true;
    }
    function closeConfirmDelete() {
        showConfirmDelete.value = false;
    }
    </script>
    
    <template>
        <AppLayout :title="role?.id ? 'Edit Role' : 'New Role'">
            <!-- Add bottom padding so fixed footer doesn't cover content -->
            <div class="p-4 pb-28">
                <form id="roleForm" @submit.prevent="submitForm" enctype="multipart/form-data">
                    <div class="flex flex-wrap justify-between">
                        <!-- Role Information Card -->
                        <Card class="w-full md:w-2/3 lg:w-1/2 mx-auto mt-2 p-4">
                            <CardTitle class="text-center">Role Information</CardTitle>
    
                            <div v-if="roleForm.id" class="pt-2 mr-2">
                                <InputLabel for="id">ID</InputLabel>
                                <TextInput id="id" v-model="roleForm.id" type="text" class="mt-1 block w-full" placeholder="ID" readonly="true" />
                            </div>
    
                            <div class="pt-2 mr-2">
                                <InputLabel for="name">Name</InputLabel>
                                <TextInput id="name" v-model="roleForm.name" type="text" class="mt-1 block w-full" required placeholder="Role Name" autocomplete="false" />
                                <InputError class="mt-2" :message="roleForm.errors.name" />
                            </div>
    
                            <div class="pt-2 mr-2">
                                <InputLabel for="slug">Slug</InputLabel>
                                <TextInput id="slug" v-model="roleForm.slug" type="text" class="mt-1 block w-full" required :placeholder="(activePermissionType || 'all') + '-role-name'" autocomplete="false" />
                                <InputError class="mt-2" :message="roleForm.errors.slug" />
                            </div>
    
                            <div class="pt-2 mr-2">
                                <InputLabel for="permissions">Permissions</InputLabel>
                                
                                <div v-if="permissionTypes.length > 0" class="mt-2 space-y-4">
                                    <!-- Type tabs -->
                                    <div class="inline-flex flex-wrap gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
                                        <button
                                            v-for="type in permissionTypes"
                                            :key="type"
                                            type="button"
                                            @click="setActivePermissionType(type)"
                                            :class="[
                                                'flex items-center rounded-md px-3.5 py-1.5 transition-colors text-sm',
                                                activePermissionType === type
                                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                            ]"
                                        >
                                            {{ typeLabel(type) }}
                                        </button>
                                    </div>

                                    <!-- Active type content -->
                                    <div v-if="activePermissionType" class="space-y-4">
                                        <div
                                            v-for="(permissions, category) in (permissionGroupsByType[activePermissionType] || {})"
                                            :key="`${activePermissionType}-${category}`"
                                            class="border rounded-md p-3"
                                        >
                                            <h3 class="font-semibold text-sm mb-2 capitalize">{{ category }}</h3>
                                            <div class="space-y-2">
                                                <label
                                                    v-for="permission in permissions"
                                                    :key="permission.slug"
                                                    class="flex items-center space-x-2 cursor-pointer hover:bg-muted/50 p-1 rounded"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :value="permissionValue(activePermissionType, permission.slug)"
                                                        :checked="
                                                            roleForm.permissions.includes(permissionValue(activePermissionType, permission.slug)) ||
                                                            roleForm.permissions.includes(permission.slug)
                                                        "
                                                        @change="togglePermission(permissionValue(activePermissionType, permission.slug), permission.slug)"
                                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                                    />
                                                    <span class="text-sm">{{ permission.name }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="mt-2 text-sm text-muted-foreground">
                                    No permissions available.
                                </div>
                                
                                <div v-if="roleForm.permissions.length > 0" class="mt-2 text-xs text-muted-foreground">
                                    {{ roleForm.permissions.length }} permission(s) selected
                                </div>
                                
                                <InputError class="mt-2" :message="roleForm.errors.permissions" />
                            </div>
    
                            <div class="pt-2 mr-2">
                                <InputLabel for="company">Company (Optional)</InputLabel>
                                <div class="text-xs text-muted-foreground mb-1">Leave blank for global role, or select a company for company-specific role</div>
                                <SmartSelect 
                                    v-model="roleForm.company_id" 
                                    id="company" 
                                    label="name" 
                                    track-by="id" 
                                    placeholder="Search companies..." 
                                    dataType="model"
                                    :endpoint="adminSelect.company.url()"
                                    class="block w-full mt-1">
                                </SmartSelect>
                                <InputError class="mt-2" :message="roleForm.errors.company_id" />
                            </div>
                        </Card>
    
                    </div>
                    <!-- Use fixed footer so actions are always visible -->
                    <div class="fixed bottom-0 right-0 left-0 md:left-[var(--sidebar-width)] z-50 bg-white/30 dark:bg-gray-900/30 backdrop-blur">
                        <div class="py-3 flex justify-center px-4">
                            <Button class="mr-2" type="submit" variant="success">
                                <Save class="mr-1" />
                                <span v-if="roleForm.id">Update</span>
                                <span v-else>Create</span>
                            </Button>
                            <Button class="mr-2" type="button" variant="neutral" @click="cancelRole()">
                                <SkipBack class="mr-1" />Cancel
                            </Button>
                            <Button v-if="role" type="button" variant="destructive" @click="openConfirmDelete">
                                <Trash2 class="mr-1" />Delete
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
        <DeleteModal :show="showConfirmDelete" item_type="role" :item_id="role?.id" :name="role?.name" @delete="deleteRole" @close="closeConfirmDelete" />
    </template>
    