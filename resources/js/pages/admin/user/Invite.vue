<template>
    <AppLayout title="Invite User to Organization">
        <div class="max-w-2xl mx-auto p-6">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-6">Invite User to Organization</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email -->
                    <div>
                        <InputLabel for="email">Email Address</InputLabel>
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="invitable_type">Invitable Type</InputLabel>
                        <SelectHorizontal id="invitable_type" v-model="form.invitable_type" :options="invitableTypes" class="mt-1 block w-full" required autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.invitable_type" />
                    </div>
                    <!-- Super Role Selection -->
                    <div v-if="form.invitable_type === 'super'">
                        <InputLabel for="super_role">Super Role</InputLabel>
                        <SmartSelect id="super_role" v-model="form.super_role" dataType="model" labelIndex="name" valueIndex="value" :data="superRoles" class="mt-1 block w-full" required placeholder="Super Role" autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.super_role" />
                    </div>
                    <!-- Company Selection -->
                    <div v-if="form.invitable_type === 'company'">
                        <InputLabel for="company_id">Company</InputLabel>
                        <SmartSelect id="company_id" v-model="form.company_id" dataType="model" labelIndex="name" :endpoint="adminSelect.company.url()" class="mt-1 block w-full" required placeholder="Company" autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.company_id" />
                    </div>

                    <!-- Client Selection -->
                    <div v-if="form.invitable_type === 'client'">
                        <InputLabel for="client_id">Client</InputLabel>
                        <SmartSelect id="client_id" v-model="form.client_id" dataType="model" labelIndex="name" :endpoint="adminSelect.client.url()" class="mt-1 block w-full" required placeholder="Client" autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.client_id" />
                    </div>

                    <!-- Contractor Selection -->
                    <div v-if="form.invitable_type === 'contractor'">
                        <InputLabel for="contractor_id">Contractor</InputLabel>
                        <SmartSelect id="contractor_id" v-model="form.contractor_id" dataType="model" labelIndex="name" :endpoint="adminSelect.contractor.url()" class="mt-1 block w-full" required placeholder="Contractor" autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.contractor_id" />
                    </div>

                    <!-- Role -->
                    <div v-if="form.invitable_type !== 'super'">
                        <InputLabel for="role_id">Role</InputLabel>
                        <SmartSelect id="role_id" v-model="form.role_id" dataType="model" labelIndex="name" :data="filteredRoles" class="mt-1 block w-full" required placeholder="Role" autocomplete="false" />
                        <InputError class="mt-2" :message="form.errors.role_id" />
                    </div>


                    <!-- Submit Button -->
                    <div class="flex items-center justify-end space-x-3">
                        <Button @click="router.visit(adminUser.list())" variant="cancel">
                        <SkipBack class="mr-1" />Cancel
                        </Button>
                        <Button type="submit" :class="{ 'opacity-25': form.processing }" variant="add" :disabled="form.processing">
                            <Send class="mr-1" />Send Invitation
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/ui/label/Label.vue';
import SelectDropdown from '@/components/ui/select/SelectDropdown.vue';
import { Button } from '@/components/ui/button';
import TextInput from '@/components/ui/input/Input.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import adminSelect from '@/routes/admin/select';
import SmartSelect from '@/components/ui/select/SmartSelect.vue';
import invitation from '@/routes/admin/invitation';
import SelectHorizontal from '@/components/ui/select/SelectHorizontal.vue';
import adminUser from '@/routes/admin/user';
import { SkipBack, Send } from 'lucide-vue-next';

const props = defineProps({
    roles: Array,
    canCreateSuperAdmin: Boolean,
    superRoles: Array,
});

const form = useForm({
    email: '',
    invitable_type: 'company',
    company_id: '',
    client_id: '',
    contractor_id: '',
    role_id: '',
});

const invitableTypes = [
    { label: 'Super', value: 'super' },
    { label: 'Company', value: 'company' },
    { label: 'Client', value: 'client' },
    { label: 'Contractor', value: 'contractor' },
];

const filteredRoles = computed(() => {
    if (!form.invitable_type || !props.roles) {
        return [];
    }
    
    return props.roles.filter(role => {
        return role.slug && role.slug.startsWith(form.invitable_type);
    });
});

onMounted(() => {
    console.log('props: ', props);
});

const submit = () => {
    form.post(invitation.create.url());
};
</script>
