<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import type { Data, User } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: '/messages',
            },
            {
                title: 'Private Chat',
                href: '/private',
            }
        ],
    },
});

const props = defineProps<{
    messages: Data[];
    authUser: User;
    receiver_id: User;
}>();
console.log(props.receiver_id.id);
const form = useForm({
    message: '',
    receiver_id: props.receiver_id.id,
});

const allMessages = ref([...props.messages]);

watch(() => props.messages, (newMessages) => {
    allMessages.value = [...newMessages];
}, { deep: true });

onMounted(() => {
    // Gunakan pengecekan yang lebih simpel
    const echo = (window as any).Echo;
    console.log(echo);

    if (echo) {
        console.log("Echo siap, mencoba subscribe...");

        echo.private(`chat.${props.authUser.id}`)
            .listen('PrivateMessageSent', (e: any) => {
                console.log("Pesan Masuk!", e);

                if (e.message) {
                    allMessages.value.push(e.message);
                }

            })
            .error((err: any) => {
                console.error("Gagal subscribe channel:", err);
            });
    }
});

const formatContent = (content: string) => {
    try {
        const parsed = JSON.parse(content);

        return parsed.value || content;
    } catch {
        // Jika bukan JSON, kembalikan teks asli
        return content;
    }
};

const submit = () => {
    console.log('Fungsi submit terpanggil tanpa reload!');

    form.post('/private', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
        },
    });
};

</script>

<template>

    <!-- Chat Display -->
    <div ref="chatContainer" class="flex-1 overflow-y-auto space-y-4 mb-4 p-6">
        <!-- Contoh mapping pesan (Jika data dikirim dari backend) -->
        <div v-for="(msg, index) in allMessages" :key="index"
            :class="msg.user_id === props.authUser.id ? 'text-right' : 'text-left'">
            <div :class="msg.user_id === props.authUser.id ? 'bg-blue-600' : 'bg-gray-700'"
                class="inline-block p-3 rounded-lg max-w-md">
                {{ formatContent(msg.message) }}
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <form @submit.prevent="submit" class="flex gap-2 p-6">
        <input v-model="form.message" type="text" placeholder="Tanya sesuatu..."
            class="flex-1 bg-gray-800 border-none rounded-lg p-3 focus:ring-2 focus:ring-blue-500"
            :disabled="form.processing" />
        <button type="button" :disabled="form.processing"
            class="bg-blue-600 px-6 py-2 rounded-lg font-bold disabled:opacity-50">
            {{ form.processing ? '...' : 'Kirim' }}
        </button>
    </form>

</template>
