<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(true);
const messages = ref([]);

const dismissMessage = (index) => {
    messages.value.splice(index, 1);
    if (messages.value.length === 0) {
        show.value = false;
    }
};

watchEffect(async () => {
    const flash = page.props.flash || {};
    const newMessages = [];
    
    // Helper function to handle both arrays and single messages
    const addMessages = (message, type) => {
        if (Array.isArray(message)) {
            // If it's an array, create a separate message entry for each item
            message.forEach(msg => {
                if (msg) { // Only add non-empty messages
                    newMessages.push({ type, message: msg });
                }
            });
        } else if (message) {
            // If it's a single message, add it as one entry
            newMessages.push({ type, message });
        }
    };
    
    // Standard Laravel flash messages
    if (flash.success) {
        addMessages(flash.success, 'success');
    }
    if (flash.error) {
        addMessages(flash.error, 'danger');
    }
    if (flash.info) {
        addMessages(flash.info, 'info');
    }
    if (flash.message) {
        // Default message type to 'info' if not specified
        addMessages(flash.message, 'info');
    }
    
    if (newMessages.length > 0) {
        messages.value = newMessages;
        show.value = true;
    } else {
        messages.value = [];
        show.value = false;
    }
});
</script>

<template>
    <div v-if="show && messages.length > 0" class="fixed top-0 left-0 right-0 z-50 flex flex-col">
        <div v-for="(msg, index) in messages" :key="index"
             :class="[
                 { 'bg-indigo-500': msg.type == 'success', 'bg-red-700': msg.type == 'danger', 'bg-blue-500': msg.type == 'info' },
                 index < messages.length - 1 ? '-mb-10' : ''
             ]">
            <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center min-w-0">
                        <span class="flex p-2 rounded-lg" :class="{ 'bg-indigo-600': msg.type == 'success', 'bg-red-600': msg.type == 'danger', 'bg-blue-600': msg.type == 'info' }">
                            <svg v-if="msg.type == 'success'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <svg v-if="msg.type == 'danger'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>

                            <svg v-if="msg.type == 'info'" class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </span>

                        <p class="ms-3 font-medium text-sm text-white whitespace-pre-line">
                            {{ msg.message }}
                        </p>
                    </div>

                    <div class="shrink-0 sm:ms-3">
                        <button
                            type="button"
                            class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition"
                            :class="{ 'hover:bg-indigo-600 focus:bg-indigo-600': msg.type == 'success', 'hover:bg-red-600 focus:bg-red-600': msg.type == 'danger', 'hover:bg-blue-600 focus:bg-blue-600': msg.type == 'info' }"
                            aria-label="Dismiss"
                            @click.prevent="dismissMessage(index)"
                        >
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
