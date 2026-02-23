<script setup>
import { onMounted } from "vue";
import { useForm, usePage, Head } from "@inertiajs/vue3";
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/ui/label/Label.vue';
import TextInput from '@/components/ui/input/Input.vue';
import TextArea from '@/components/ui/input/TextArea.vue';
import Button from '@/components/ui/button/Button.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Card from "@/components/ui/card/Card.vue";
import CardTitle from "@/components/ui/card/CardTitle.vue";
import { contact } from '@/routes/policies';

const props = defineProps({
    sent: Boolean,
    recaptcha_sitekey: String,
});
const page = usePage();
const form = useForm({
    name: "",
    email: "",
    subject: "",
    details: "",
    grecaptcha_response: "",
});

const submit = () => {
    let submitForm = (token) => {
        form.grecaptcha_response = token;
        form.post(contact.save.url(), {
            onSuccess: () => props.sent = true,
            onError: (error) => { console.log(error); form.errors = error },
        });
    };
    grecaptcha
        .enterprise.execute(props.recaptcha_sitekey, { action: "submit" })
        .then(function (token) {
            submitForm(token);
        });
};

onMounted(() => {
    console.log('recaptcha:', props.recaptcha_sitekey);
    let recaptchaScript = document.createElement('script')
    recaptchaScript.setAttribute('src', 'https://www.google.com/recaptcha/enterprise.js?render=' + props.recaptcha_sitekey);
    recaptchaScript.setAttribute('type', 'application/javascript');
    recaptchaScript.setAttribute('async', 'async');
    recaptchaScript.setAttribute('defer', 'defer');
    document.getElementById('contactus').appendChild(recaptchaScript);
});
</script>

<template>
    <AppLayout title="Contact">
        <Head title="Contact Us" />
        <div class="font-sans text-gray-900 antialiased">
            <Card class="w-full md:w-96 mx-auto mt-2 p-4">
                <CardTitle class="text-center">
                    <div class="mx-auto flex aspect-square size-32 items-center justify-center">
                        <AppLogoIcon class="size-32 fill-current text-white" />
                    </div>
                    <div>Contact</div>
                </CardTitle>
                <p v-if="sent">Thank you {{ name }} your request has been sent.</p>
                <form v-else id="contactus" class="w-full" @submit.prevent="submit">
                    <input type="hidden" name="grecaptcha_response">
                    <div v-if="!page.props.auth.user">
                        <div class="w-full mt-2">
                            <InputLabel for="name">Name</InputLabel>
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div class="w-full mt-2">
                            <InputLabel for="email">E-mail</InputLabel>
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="w-full mt-2">
                        <InputLabel for="subject">Subject</InputLabel>
                        <TextInput
                            id="subject"
                            v-model="form.subject"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Subject"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.subject" />
                    </div>

                    <div class="w-full mt-2">
                        <InputLabel for="details">Details</InputLabel>
                        <TextArea
                            id="details"
                            v-model="form.details"
                            type="textarea"
                            class="mt-1 block w-full"
                            placeholder="Details"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.details" />
                    </div>

                    <p class="text-sm p-2">This site is protected by reCAPTCHA and the Google
                    <a href="https://policies.google.com/privacy" target="_blank" class="underline">Privacy Policy</a> and
                    <a href="https://policies.google.com/terms" target="_blank" class="underline">Terms of Service</a> apply.</p>
                    <InputError class="mt-2" :message="form.errors.grecaptcha_response" />
                    <Button class="mt-4" type="submit" variant="save" :data-sitekey="sitekey" data-callback='onSubmit' data-action='submit'>{{ $t('Submit') }}</Button>
                </form>
            </Card>  
        </div>
    </AppLayout>

</template>
