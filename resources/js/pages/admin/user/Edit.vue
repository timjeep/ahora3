<script setup>
import { useForm, usePage, router, Head } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import adminUser from '@/routes/admin/user';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/ui/label/Label.vue';
import TextInput from '@/components/ui/input/Input.vue';
import SelectDropdown from '@/components/ui/select/SelectDropdown.vue';
import SmartSelect from '@/components/ui/select/SmartSelect.vue';
import Toggle from '@/components/ui/checkbox/Toggle.vue';
import Card from "@/components/ui/card/Card.vue";
import CardTitle from "@/components/ui/card/CardTitle.vue";
import CardContent from "@/components/ui/card/CardContent.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import select from '@/routes/select';
import adminSelect from '@/routes/admin/select';
import { Save, Trash2, SkipBack, Plus, X, Mail, CheckCircle2, XCircle, ShieldUser, ShieldOff, ArchiveRestore } from "lucide-vue-next";

const props = defineProps({
    user: Object,
    provider_logins: Object,
    allCompanies: Array,
    allClients: Array,
    allContractors: Array,
    superRoles: Object,
    timezones: Array,
    units: [Array, Object],
    has_mfa: { type: Boolean, default: false },
});

onMounted(() => {
    console.log('props: ', props);
});

const availableCompanyRoles = computed(() => {
    return props.allCompanies.find(c => c.id === newCompany.value.company_id)?.availableRoles || [];
});

const availableClientRoles = computed(() => {
    return props.allClients.find(c => c.id === newClient.value.client_id)?.availableRoles || [];
});

const availableContractorRoles = computed(() => {
    return props.allContractors.find(c => c.id === newContractor.value.contractor_id)?.availableRoles || [];
});

const userForm = useForm({
    id: null,
    username: null,
    name: "",
    email: null,
    password: "",
    country_code: null,
    timezone: null,
    units: null,
    super_role: 'none',
    companies: [],
    clients: [],
    contractors: [],
    password_confirmation: "",
    disabled: false,
});

// Company management
const newCompany = ref({
    company_id: '',
    role: null
});
const addCompanyError = ref(null);

const newClient = ref({
    client_id: '',
    role: null
});
const addClientError = ref(null);

const newContractor = ref({
    contractor_id: '',
    role: null
});
const addContractorError = ref(null);

onMounted(() => {
    //    console.log('props.user: ',props?.user);
    console.log('props: ', props);
    if (props.user) {
        Object.assign(userForm, {
            id: props.user.id,
            username: props.user.username,
            name: props.user.name,
            email: props.user.email,
            country_code: props.user.country_code,
            timezone: props.user.timezone,
            units: props.user.units,
            super_role: props.user.super_role || 'none',
            disabled: props.user.disabled_at ? true : false,
            companies: props.user.companies ? props.user.companies.map(company => ({
                company_id: company.id,
                // backend uses pivot.role_id (UUID FK to roles)
                role: company.pivot?.role_id ?? company.pivot?.role ?? null,
            })) : [],
            clients: props.user.clients ? props.user.clients.map(client => ({
                client_id: client.id,
                role: client.pivot?.role_id ?? client.pivot?.role ?? null,
            })) : [],
            contractors: props.user.contractors ? props.user.contractors.map(contractor => ({
                contractor_id: contractor.id,
                role: contractor.pivot?.role_id ?? contractor.pivot?.role ?? null,
            })) : [],
        });
    }
    console.log('userForm: ', userForm);
});

const submitForm = () => (userForm.id ? updateUser() : addUser());
const updateUser = () => userForm.put(adminUser.update.url([props.user.id]));
const addUser = () => userForm.post(adminUser.create.url());
const deleteUser = () => userForm.delete(adminUser.delete.url([props.user.id]));
const cancelUser = () => {
    router.visit(adminUser.list.url());
};

// Company management functions
const addCompany = () => {
    if (newCompany.value.company_id && newCompany.value.role) {
        // Check if company is already added
        const exists = userForm.companies.some(comp => comp.company_id === newCompany.value.company_id);
        if (!exists) {
            userForm.companies.push({ ...newCompany.value });
            newCompany.value = { company_id: '', role: null };
            addCompanyError.value = null;
        } else {
            addCompanyError.value = 'Company already added';
        }
    } else {
        addCompanyError.value = 'Please select a company and role';
    }
};

const removeCompany = (index) => {
    userForm.companies.splice(index, 1);
};

const getCompanyName = (companyId) => {
    const company = props.allCompanies.find(c => c.id === companyId);
    return company ? company.name : 'Unknown Company';
};

const getCompanyRole = (companyAssociation) => {
    // `userForm.companies` items look like: { company_id, role }
    const roleId = companyAssociation?.role ?? companyAssociation?.role_id;
    if (!roleId) return 'No role';

    const company = props.allCompanies.find(c => c.id === companyAssociation.company_id);
    const availableRoles = company?.availableRoles;

    // `availableRoles` is typically an object map: { [roleId]: "Role Name" }
    if (availableRoles && typeof availableRoles === 'object') {
        return availableRoles[roleId] || roleId;
    }

    return roleId;
};

const addClient = () => {
    if (newClient.value.client_id && newClient.value.role) {
        userForm.clients.push({ ...newClient.value });
        newClient.value = { client_id: '', role: null };
        addClientError.value = null;
    } else {
        addClientError.value = 'Please select a client and role';
    }
};

const removeClient = (index) => {
    userForm.clients.splice(index, 1);
};

const getClientName = (clientId) => {
    const client = props.allClients.find(c => c.id === clientId);
    return client ? client.name : 'Unknown Client';
};

const getClientRole = (clientAssociation) => {
    const roleId = clientAssociation?.role;
    if (!roleId) return 'No role';

    const client = props.allClients.find(c => c.id === clientAssociation.client_id);
    const availableRoles = client?.availableRoles;

    if (availableRoles && typeof availableRoles === 'object') {
        return availableRoles[roleId] || roleId;
    }

    return roleId;
};

const addContractor = () => {
    if (newContractor.value.contractor_id && newContractor.value.role) {
        userForm.contractors.push({ ...newContractor.value });
        newContractor.value = { contractor_id: '', role: null };
        addContractorError.value = null;
    } else {
        addContractorError.value = 'Please select a contractor and role';
    }
};

const removeContractor = (index) => {
    userForm.contractors.splice(index, 1);
};

const getContractorName = (contractorId) => {
    const contractor = props.allContractors.find(c => c.id === contractorId);
    return contractor ? contractor.name : 'Unknown Contractor';
};

const getContractorRole = (contractorAssociation) => {
    const roleId = contractorAssociation?.role;
    if (!roleId) return 'No role';

    const contractor = props.allContractors.find(c => c.id === contractorAssociation.contractor_id);
    const availableRoles = contractor?.availableRoles;

    if (availableRoles && typeof availableRoles === 'object') {
        return availableRoles[roleId] || roleId;
    }

    return roleId;
};

const showConfirmDelete = ref(false);
function openConfirmDelete() {
    showConfirmDelete.value = true;
}
function closeConfirmDelete() {
    showConfirmDelete.value = false;
}

// Send reset password email
const sendingResetEmail = ref(false);
const sendResetPasswordEmail = () => {
    if (!props.user?.id) return;

    sendingResetEmail.value = true;
    router.post(adminUser.send - reset - password.url([props.user.id]), {}, {
        onSuccess: () => {
            sendingResetEmail.value = false;
        },
        onError: () => {
            sendingResetEmail.value = false;
        }
    });
};
const restoreUser = () => router.post(adminUser.restore.url([props.user.id]));
const forceDeleteUser = () => router.delete(adminUser.forceDelete.url([props.user.id]));
</script>

<template>
    <AppLayout :title="user?.id ? 'Edit User' : 'New User'">
        <!-- Add bottom padding so fixed footer doesn't cover content -->
        <div class="p-4 pb-28">
            <form id="userForm" @submit.prevent="submitForm" enctype="multipart/form-data">
                <div class="mx-auto max-w-7xl p-4 columns-[18rem] sm:columns-[20rem] lg:columns-[22rem] xl:columns-[24rem] gap-x-4">
                    <!-- User Information Card -->
                    <Card class="w-full mb-4 break-inside-avoid">
                        <CardTitle class="text-center my-2">User Information</CardTitle>
                        <CardContent>
                            <div v-if="userForm.id" class="pt-2 mr-2">
                                <InputLabel for="id">ID</InputLabel>
                                <TextInput id="id" v-model="userForm.id" type="text" class="mt-1 block w-full" placeholder="ID" readonly="true" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="username">MDC Username</InputLabel>
                                <TextInput id="username" v-model="userForm.username" type="text" class="mt-1 block w-full" placeholder="MDC Username" autocomplete="false" />
                                <InputError class="mt-2" :message="userForm.errors.username" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="name">Name</InputLabel>
                                <TextInput id="name" v-model="userForm.name" type="text" class="mt-1 block w-full" required placeholder="User Name" autocomplete="false" />
                                <InputError class="mt-2" :message="userForm.errors.name" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="email">Email</InputLabel>
                                <TextInput id="email" v-model="userForm.email" type="email" class="mt-1 block w-full" required placeholder="Email" autocomplete="false" />
                                <InputError class="mt-2" :message="userForm.errors.email" />
                            </div>

                            <div v-if="!userForm.id" class="pt-2 mr-2">
                                <InputLabel for="password">Password</InputLabel>
                                <TextInput id="password" v-model="userForm.password" type="password" class="mt-1 block w-full" :required="!userForm.id" placeholder="Password"
                                    autocomplete="new-password" />
                                <InputError class="mt-2" :message="userForm.errors.password" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="country_code">Country</InputLabel>
                                <SmartSelect id="country_code" v-model="userForm.country_code" placeholder="Search countries..." dataType="model" :endpoint="select.country.url()" queryParam="search"
                                    :minChars="0" :multiple="false" labelIndex="name" valueIndex="code" class="block w-full mt-1" autocomplete="false" />
                                <InputError class="mt-2" :message="userForm.errors.country_code" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="timezone">Timezone</InputLabel>
                                <SmartSelect id="timezone" v-model="userForm.timezone" placeholder="Search timezones..." dataType="list" :data="timezones" queryParam="search" :minChars="0"
                                    :multiple="false" class="block w-full mt-1" autocomplete="false" />
                                <InputError class="mt-2" :message="userForm.errors.timezone" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="units">Units</InputLabel>
                                <SelectDropdown id="units" v-model="userForm.units" dataType="assoc" :data="units" class="mt-1 block w-full" placeholder="Units" />
                                <InputError class="mt-2" :message="userForm.errors.units" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="userForm.id" class="w-full mb-4 break-inside-avoid">
                        <CardTitle class="text-center py-2">Other Information</CardTitle>
                        <CardContent>
                            <div class="space-y-2 pt-2">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Login</div>
                                    <div class="text-base text-gray-900 dark:text-gray-100">{{ props.user?.lastlogin_at ? new Date(props.user.lastlogin_at).toLocaleString() : 'Never' }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified At</div>
                                    <div class="text-base text-gray-900 dark:text-gray-100">{{ props.user?.email_verified_at ? new Date(props.user.email_verified_at).toLocaleString() : 'Never' }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Disabled At</div>
                                    <div class="text-base text-gray-900 dark:text-gray-100">{{ props.user?.disabled_at ? new Date(props.user.disabled_at).toLocaleString() : 'Never' }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</div>
                                    <div class="text-base text-gray-900 dark:text-gray-100">{{ props.user?.created_at ? new Date(props.user.created_at).toLocaleString() : 'Never' }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</div>
                                    <div class="text-base text-gray-900 dark:text-gray-100">{{ props.user?.updated_at ? new Date(props.user.updated_at).toLocaleString() : 'Never' }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Company Associations Card -->
                    <Card class="w-full mb-4 break-inside-avoid">
                        <CardTitle class="text-center my-2">Access</CardTitle>
                        <CardContent>
                            <!-- Add New Company -->
                            <div class="pt-2 mr-2">
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <InputLabel for="new_company">Company</InputLabel>
                                        <SmartSelect id="new_company" v-model="newCompany.company_id" dataType="model" labelIndex="name" :endpoint="adminSelect.company.url()" :minChars="0"
                                            :multiple="false" valueIndex="id" class="mt-1 block w-full" placeholder="Select Company" autocomplete="false" />
                                    </div>
                                    <div class="w-32">
                                        <InputLabel for="new_role">Role</InputLabel>
                                        <SelectDropdown id="new_role" v-model="newCompany.role" dataType="assoc" :data="availableCompanyRoles" class="mt-1 block w-full" placeholder="Role" />
                                    </div>
                                    <div class="flex items-end">
                                        <Button type="button" @click="addCompany" variant="success" class="h-10">
                                            <Plus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="addCompanyError"></InputError>
                            </div>
                            <!-- Add New Contractor -->
                            <div class="pt-2 mr-2">
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <InputLabel for="new_contractor">Contractor</InputLabel>
                                        <SmartSelect id="new_contractor" v-model="newContractor.contractor_id" dataType="model" labelIndex="name" :endpoint="adminSelect.contractor.url()" :minChars="0"
                                            :multiple="false" valueIndex="id" class="mt-1 block w-full" placeholder="Select Contractor" />
                                    </div>
                                    <div class="w-32">
                                        <InputLabel for="new_contractor_role">Role</InputLabel>
                                        <SelectDropdown id="new_contractor_role" v-model="newContractor.role" dataType="assoc" :data="availableContractorRoles" class="mt-1 block w-full"
                                            placeholder="Role" />
                                    </div>
                                    <div class="flex items-end">
                                        <Button type="button" @click="addContractor" variant="success" class="h-10">
                                            <Plus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="addContractorError"></InputError>
                            </div>

                            <!-- Add New Client -->
                            <div class="pt-2 mr-2">
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <InputLabel for="new_client">Client</InputLabel>
                                        <SmartSelect id="new_client" v-model="newClient.client_id" dataType="model" labelIndex="name" :endpoint="adminSelect.client.url()" :minChars="0"
                                            :multiple="false" valueIndex="id" class="mt-1 block w-full" placeholder="Select Client" />
                                    </div>
                                    <div class="w-32">
                                        <InputLabel for="new_client_role">Role</InputLabel>
                                        <SelectDropdown id="new_client_role" v-model="newClient.role" dataType="assoc" :data="availableClientRoles" class="mt-1 block w-full" placeholder="Role" />
                                    </div>
                                    <div class="flex items-end">
                                        <Button type="button" @click="addClient" variant="success" class="h-10">
                                            <Plus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="addClientError"></InputError>
                            </div>
                            <!-- Current Company Associations -->
                            <div class="pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Associations</h4>
                                <div v-if="userForm.companies.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                                    No company associations
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="(company, index) in userForm.companies" :key="index" class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ getCompanyName(company.company_id) }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ getCompanyRole(company) }}</div>
                                        </div>
                                        <Button type="button" @click="removeCompany(index)" variant="destructive" size="sm">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="userForm.errors.companies" />

                            <!-- Current Contractor Associations -->
                            <div class="pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Contractor Associations</h4>
                                <div v-if="userForm.contractors.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                                    No contractor associations
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="(contractor, index) in userForm.contractors" :key="index" class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ getContractorName(contractor.contractor_id) }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ getContractorRole(contractor) }}</div>
                                        </div>
                                        <Button type="button" @click="removeContractor(index)" variant="destructive" size="sm">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="userForm.errors.contractors" />

                            <!-- Current Client Associations -->
                            <div class="pt-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Client Associations</h4>
                                <div v-if="userForm.clients.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                                    No client associations
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="(client, index) in userForm.clients" :key="index" class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ getClientName(client.client_id) }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ getClientRole(client) }}</div>
                                        </div>
                                        <Button type="button" @click="removeClient(index)" variant="destructive" size="sm">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="userForm.errors.clients" />

                        </CardContent>
                    </Card>

                    <Card class="w-full mb-4 break-inside-avoid">
                        <CardTitle class="text-center my-2">Security</CardTitle>
                        <CardContent>
                            <div class="pt-2 mr-2">
                                <InputLabel for="disabled" class="flex items-center">
                                    <Toggle id="disabled" v-model="userForm.disabled" class="mr-1" autocomplete="false" />
                                    <span>Disabled</span>
                                </InputLabel>
                                <InputError class="mt-2" :message="userForm.errors.disabled" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="super_role">Super Admin Role</InputLabel>
                                <SelectDropdown id="super_role" v-model="userForm.super_role" dataType="assoc" :data="superRoles" class="mt-1 block w-full" placeholder="Select Super Admin Role" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Global application permissions (separate from company-specific roles below)
                                </p>
                                <InputError class="mt-2" :message="userForm.errors.super_role" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="password">Password</InputLabel>
                                <TextInput id="password" v-model="userForm.password" type="password" class="mt-1 block w-full" placeholder="Password" autocomplete="new-password" />
                                <InputError class="mt-2" :message="userForm.errors.password" />
                            </div>

                            <div class="pt-2 mr-2">
                                <InputLabel for="password_confirmation">Password Confirmation</InputLabel>
                                <TextInput id="password_confirmation" v-model="userForm.password_confirmation" type="password" class="mt-1 block w-full" placeholder="Password Confirmation"
                                    autocomplete="new-password" />
                                <InputError class="mt-2" :message="userForm.errors.password_confirmation" />
                            </div>

                            <div v-if="user?.id" class="pt-4 mr-2">
                                <Button type="button" variant="outline" class="w-full" @click="sendResetPasswordEmail" :disabled="sendingResetEmail">
                                    <Mail class="mr-2 h-4 w-4" />
                                    {{ sendingResetEmail ? 'Sending...' : 'Send Reset Password Email' }}
                                </Button>
                            </div>

                            <div class="flex flex-wrap gap-4 pt-4 mr-2">
                                <div v-for="(connected, provider) in provider_logins" :key="provider" class="relative flex flex-col items-center gap-1" :class="{ 'opacity-30': !connected }"
                                    :title="connected ? (`${provider} connected` + (provider === 'email' ? (props.has_mfa ? ' (MFA)' : ' No MFA') : '')) : `${provider} not connected`">
                                    <img :src="`/social/social-${provider}.png`" :alt="provider" class="w-12 h-12 rounded-md transition-opacity bg-gray-500" />
                                    <CheckCircle2 v-if="connected" class="absolute -top-1 -right-1 w-5 h-5 text-green-500 bg-white dark:bg-gray-800 rounded-full" />
                                    <XCircle v-else class="absolute -top-1 -right-1 w-5 h-5 text-red-500 bg-white dark:bg-gray-800 rounded-full" />
                                    <template v-if="provider === 'email'">
                                        <ShieldUser v-if="props.has_mfa" class="absolute -top-1 -left-1 w-5 h-5 text-green-500 bg-white dark:bg-gray-800 rounded-full" />
                                        <ShieldOff v-else class="absolute -top-1 -left-1 w-5 h-5 text-red-500 bg-white dark:bg-gray-800 rounded-full" />
                                    </template>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="fixed bottom-0 right-0 left-0 md:left-[var(--sidebar-width)] z-50 bg-white/30 dark:bg-gray-900/30 backdrop-blur">
                    <div class="py-3 flex justify-center">
                        <Button class="mr-2" type="submit" variant="success">
                            <Save class="mr-1" />
                            <span v-if="userForm.id">Update</span>
                            <span v-else>Create</span>
                        </Button>
                        <Button class="mr-2" type="button" variant="neutral" @click="cancelUser()">
                            <SkipBack class="mr-1" />Cancel
                        </Button>
                        <Button v-if="user && !user.deleted_at" type="button" variant="destructive" @click="openConfirmDelete">
                            <Trash2 class="mr-1" />Delete
                        </Button>
                        <Button v-if="user && user.deleted_at" type="button" variant="success" @click="restoreUser()" class="mr-2">
                            <ArchiveRestore class="mr-1" />Restore
                        </Button>
                        <Button v-if="user && user.deleted_at" type="button" variant="destructive" @click="forceDeleteUser()">
                            <Trash2 class="mr-1" />Force Delete
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
    <DeleteModal :show="showConfirmDelete" item_type="user" :item_id="user?.id" :name="user?.name" @delete="deleteUser" @close="closeConfirmDelete" />
</template>