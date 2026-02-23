<script setup>
    import { ref, onMounted } from "vue";
    import Modal from '@/components/Modal.vue';
    import Button from '@/components/ui/button/Button.vue';
    import { Trash2, SkipBack } from "lucide-vue-next";
    
    const emit = defineEmits(['close','delete']);
    
    const props = defineProps({
        show: {
            type: Boolean,
            default: false,
        },
        maxWidth: {
            type: String,
            default: 'sm',
        },
        closeable: {
            type: Boolean,
            default: true,
        },
        item_id: {
            type: String,
            default: '',
        },
        item_type: {
            type: String,
            default: 'item',
        },
        name: {
            type: String,
            default: '',
        },
    });
    const current_item_id = ref(null);
    const current_item_type= ref('item');
    const current_name = ref(null);
    defineExpose({
        changeDelete,
    })
    function changeDelete(item_id, item_type, name){
        current_item_id.value = item_id;
        current_item_type.value = item_type;
        current_name.value = name;
    }
    const close = () => {
        emit('close');
    };
    const deleteItem = () => {
        emit('delete',current_item_type.value,current_item_id.value);
        close();
    }
    onMounted(() => {
        current_item_id.value = props.item_id;
        current_item_type.value = props.item_type;
        current_name.value = props.name;
    });
    
    </script>
    
    <template>
        <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <span>Delete {{ current_item_type }}</span>
                </div>
    
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete this {{ current_item_type }} <span v-if="current_name">"<span class="font-bold">{{ current_name }}</span>"</span>
                </div>
            </div>
    
            <div class="flex flex-row justify-end px-6 py-2 bg-gray-100 dark:bg-gray-700 text-end">
                <Button variant="cancel" @click.prevent="close()" type="button" class="mr-2">
                    <SkipBack class="mr-1" />Cancel
                </Button>
                <Button variant="delete" @click.prevent="deleteItem()" type="button">
                    <Trash2 class="mr-1" />Delete
                </Button>
            </div>
        </Modal>
    </template>
    