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
        <Head title="Privacy" />
        <div class="font-sans text-gray-900 antialiased">
            <Card class="w-full mx-auto mt-2 p-4">
                <CardTitle class="text-center">
                    <div class="mx-auto flex aspect-square size-32 items-center justify-center">
                        <AppLogoIcon class="fill-current text-white" />
                    </div>
                    <div>Privacy Policy for MDC</div>
                </CardTitle>
                <CardContent>
                    <p>We like privacy!</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 
