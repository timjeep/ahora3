<script setup>
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head } from "@inertiajs/vue3";
    import AppLogoIcon from '@/components/AppLogoIcon.vue';
    import Card from "@/components/ui/card/Card.vue";
    import CardTitle from "@/components/ui/card/CardTitle.vue";
    import CardContent from "@/components/ui/card/CardContent.vue";
</script>

<template>
    <AppLayout>
        <Head title="Data Deletion" />
        <div class="font-sans text-gray-900 antialiased">
            <Card class="w-full mx-auto mt-2 p-4">
                <CardTitle class="text-center">
                    <div class="mx-auto flex aspect-square size-32 items-center justify-center">
                        <AppLogoIcon class="fill-current text-white" />
                    </div>
                    <div>Data Deletion Policy for MDC</div>
                </CardTitle>
                <CardContent>
                    <p>You may be deleted!</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 
