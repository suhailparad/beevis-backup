<script setup>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Head,useForm } from '@inertiajs/inertia-vue3';
import BreezeButton from '@/Components/Button.vue';
import BreezeInput from '@/Components/Input.vue';
import { ref,onMounted } from 'vue';

const props = defineProps({
    flash: Object,
});

const form = useForm({
    limit: 10000,
    offset: 10000,
});


const submit = () => {
    form.post(route('migrate.users'), {
        onFinish: () => {
            form.offset+=form.limit;
        }
    });
};

</script>

<template>
    <Head title="Dashboard" />

    <BreezeAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h5 class="mb-5">Platoshop Migration Tool!</h5>

                        <div class="flex">
                             <div>
                                Limit:
                                <BreezeInput id="offset" name="limit" type="text" class="inline-block " v-model="form.limit" required  />
                            </div>
                            <div class="ml-4">
                                Offset:
                                <BreezeInput id="limit" name="offset" type="text" class="inline-block" v-model="form.offset" required />
                            </div>
                            <div>
                                <BreezeButton @click="submit" class="ml-4 h-[40px]" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Start Migration
                                </BreezeButton>
                            </div>
                        </div>

                        <span class="mt-8 text-gray-500 text-md block">Result</span>
                        <div class="border border-gray-200 block mt-1 p-10 bg-slate-50 rounded">
                            <template v-if="form.processing">
                                <span class="block text-slate-500 text-sm">
                                    <svg role="status" class="inline mt-[-5px] mr-2 w-4 h-4 text-gray-200 animate-spin dark:text-gray-600 fill-gray-600 dark:fill-gray-300" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                                    </svg>
                                    Migrating, Please wait...</span>
                            </template>
                            <template v-if="flash.success">
                                <span class="text-green-600">{{flash.success}}</span>
                            </template>
                            <template v-if="flash.error">
                                <span class="text-red-600">{{flash.error}}</span>
                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </BreezeAuthenticatedLayout>
</template>
