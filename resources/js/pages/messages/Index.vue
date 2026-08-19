<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import type { Message, User } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: '/messages',
            },
        ],
    },
});

// ==============================
// GANTI bagian ini dengan data asli dari controller / Inertia props, contoh:
// const props = defineProps({ users: Array })
// ==============================
const props = defineProps<{
    users: Message[];
    allUsers: User[];
    authId: number;
}>();
console.log(props.users);

const messages = ref([...props.users]);

watch(() => props.users, (newUsers) => {
    messages.value = [...newUsers];
}, { deep: true });

onMounted(() => {
    // Gunakan pengecekan yang lebih simpel
    const echo = (window as any).Echo;
    console.log(echo);

    if (echo) {
        console.log("Echo siap, mencoba subscribe...");

        echo.private(`chat.${props.authId}`)
            .listen('PrivateMessageSent', (e: any) => {
                console.log("Pesan Masuk!", e);
                console.log("Pesan Masuk!", e.message);

                if (e.conversation) {

                    const newConversation = e.conversation;

                    const index = messages.value.findIndex(
                        (item) =>
                            Number(item.id) === Number(newConversation.id)
                    );

                    if (index !== -1) {

                        messages.value[index] = {
                            ...messages.value[index],
                            ...newConversation,
                        };

                    } else {

                        messages.value.unshift(newConversation);

                    }
                }

            })
            .error((err: any) => {
                console.error("Gagal subscribe channel:", err);
            });
    }
});

// Navigasi ke route /private/{id} ketika user diklik
function openChat(id: number) {
    router.get(`/private/${id}`);
}

const showModal = ref(false);
const search = ref('');

const filteredAllUsers = computed(() => {
    if (!search.value.trim()) {
        return props.allUsers;
    }

    return props.allUsers.filter((u) =>
        u.name.toLowerCase().includes(search.value.toLowerCase()),
    );
});

function openModal() {
    search.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function startNewChat(id: number) {
    closeModal();
    openChat(id);
}
</script>

<template>

    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col overflow-hidden p-2">
        <!-- Header: tombol tambah chat -->
        <div class="mb-2 flex items-center justify-between px-1">
            <span class="text-sm font-medium text-neutral-500">Chat</span>
            <button type="button" @click="openModal" title="Chat baru"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-green-500 text-white transition-colors hover:bg-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </button>
        </div>

        <!-- List user -->
        <div class="flex-1 overflow-y-auto">
            <button v-for="user in messages" :key="user.id" type="button" @click="openChat(user.id)"
                class="cursor-pointer rounded-xl flex w-full items-center gap-3 border border-sidebar-border/70 dark:border-sidebar-border p-3 text-left transition-colors hover:bg-black/5 dark:hover:bg-white/5 mb-2">
                <div class="relative shrink-0">
                    <img :src="user.avatar" :alt="user.name" class="h-11 w-11 rounded-full object-cover" />
                    <span v-if="user.online"
                        class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500 dark:border-neutral-900" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <span class="truncate font-medium">{{ user.name }}</span>
                        <span class="shrink-0 text-xs text-neutral-500">{{ user.time }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="truncate text-sm text-neutral-500">{{ user.lastMessage }}</span>
                        <!-- <span v-if="user.unread"
                            class="ml-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-green-500 px-1 text-xs font-semibold text-white">
                            {{ user.unread }}
                        </span> -->
                    </div>
                </div>
            </button>

            <div v-if="props.users.length === 0" class="p-4 text-center text-sm text-neutral-500">
                Belum ada kontak
            </div>
        </div>

        <!-- Modal: semua user untuk mulai chat baru -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeModal">
            <div
                class="flex max-h-[80vh] w-full max-w-md flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-900">
                <!-- Header modal -->
                <div
                    class="flex items-center justify-between border-b border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <span class="font-medium">Chat baru</span>
                    <button type="button" @click="closeModal"
                        class="rounded-full p-1 hover:bg-black/5 dark:hover:bg-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Search -->
                <div class="p-2">
                    <input v-model="search" type="text" placeholder="Cari nama user"
                        class="w-full rounded-lg border border-sidebar-border/70 bg-transparent px-3 py-2 text-sm outline-none focus:border-sidebar-border dark:border-sidebar-border" />
                </div>

                <!-- List semua user -->
                <div class="flex-1 overflow-y-auto p-2 pt-0">
                    <button v-for="user in filteredAllUsers" :key="user.id" type="button" @click="startNewChat(user.id)"
                        class="flex w-full items-center gap-3 rounded-xl p-3 text-left transition-colors hover:bg-black/5 dark:hover:bg-white/5">
                        <img :src="user.avatar" :alt="user.name" class="h-10 w-10 rounded-full object-cover" />
                        <span class="truncate font-medium">{{ user.name }}</span>
                    </button>

                    <div v-if="filteredAllUsers.length === 0" class="p-4 text-center text-sm text-neutral-500">
                        User tidak ditemukan
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
