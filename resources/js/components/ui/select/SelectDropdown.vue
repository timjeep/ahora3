<script setup lang="ts">
    import { computed } from 'vue';
    import { cn } from '@/lib/utils';
    import { useVModel } from '@vueuse/core';
    import type { HTMLAttributes } from 'vue';

    type DataItem = Record<string, unknown>;
    type DataProp = DataItem[] | Record<string, string>;

    const props = withDefaults(
        defineProps<{
            defaultValue?: string | number;
            modelValue?: string | number;
            keyIndex?: string;
            valueIndex?: string;
            labelIndex?: string;
            dataType?: string;
            data: DataProp;
            placeholder?: string;
            class?: HTMLAttributes['class'];
        }>(),
        {
            keyIndex: 'id',
            valueIndex: 'id',
            labelIndex: 'name',
            dataType: 'model',
        }
    );

    const dataArray = computed<DataItem[]>(() =>
        Array.isArray(props.data) ? props.data : []
    );

    const emits = defineEmits<{
        (e: 'update:modelValue', payload: string | number): void;
    }>();

    const modelValue = useVModel(props, 'modelValue', emits, {
        passive: true,
        defaultValue: props.defaultValue,
    });
    </script>
    
    <template>
        <select 
            ref="input"
            data-slot="select"
            :value="modelValue || ''"
            @change="(e) => $emit('update:modelValue', (e.target as HTMLSelectElement).value)"
            :class="
                cn(
                    'placeholder:text-muted-foreground dark:bg-input/30 border-input h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                    props.class,
                )
            "	>
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option v-if="dataType=='list'" :key="index" :value="label" v-for="(label,index) in data">{{ label }}</option>
            <option v-if="dataType=='array'" :key="index" :value="index" v-for="(label,index) in data">{{ label }}</option>
            <option v-if="dataType=='assoc'" :key="key" :value="key" v-for="(label, key) in data">{{ label }}</option>
            <option v-if="dataType=='model'" :key="String((item as DataItem).id)" :value="(item as DataItem)['id']" v-for="item in dataArray">{{ (item as DataItem)[labelIndex] }}</option>
            <option v-if="dataType=='object'" :key="String((item as DataItem)[keyIndex])" :value="(item as DataItem)[valueIndex]" v-for="item in dataArray">{{ (item as DataItem)[labelIndex] }}</option>
        </select>
    </template>