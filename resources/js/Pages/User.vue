<script setup>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { Head,useForm } from '@inertiajs/inertia-vue3';
import BreezeButton from '@/Components/Button.vue';
import BreezeInput from '@/Components/Input.vue';
import { onMounted } from 'vue';

const props = defineProps({
    flash: Object,
});

const form = useForm({
    start_date: '',
    end_date: ''
});

const submit = () => {
    form.post(route('migrate.users'), {
        onFinish: () => {

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
                                <BreezeButton @click="submit" class="ml-4 h-[40px]" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Start Migration
                                </BreezeButton>
                            </div>
                        </div>

                        <span class="mt-8 text-gray-500 text-md block">Result</span>
                        <div class="border border-gray-200 block mt-1 p-10 bg-slate-50 rounded">

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </BreezeAuthenticatedLayout>
</template>
