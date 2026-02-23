<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type NavItem } from '@/types';
import { ChevronDown } from 'lucide-vue-next';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

function hasActiveChild(item: NavItem): boolean {
    if (!item.children?.length) {
        return false;
    }
    return item.children.some((child) => isCurrentUrl(child.href));
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <Collapsible
                    v-if="item.children?.length"
                    :default-open="hasActiveChild(item)"
                    class="group/collapsible"
                >
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :is-active="hasActiveChild(item)"
                            :tooltip="item.title"
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <ChevronDown
                                class="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-180"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="child in item.children"
                                :key="child.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl(child.href)"
                                >
                                    <Link :href="child.href">
                                        <component :is="child.icon" />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
