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
                            <template v-if="flash.success">

                            </template>
                            <template v-if="flash.error">

                            </template>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </BreezeAuthenticatedLayout>
</template>
