<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import InputErrors from '@/components/InputErrors.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from '@/utils/route'

const props = defineProps<{
    email: string,
    address: string,
}>()

const form = useForm({
    name: '',
    email: '',
    message: '',
})

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <AppLayout
        title="Contact us"
        description="Get in touch with Talent Referral Platform."
    >
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-semibold">
                Contact us
            </h1>
            <p class="text-muted-color">
                Have a question or feedback? Send us a message.
            </p>
            <div class="space-y-3">
                <p>
                    Email:
                    <a
                        :href="`mailto:${props.email}`"
                        class="text-primary underline"
                    >{{ props.email }}</a>
                </p>
                <p class="whitespace-pre-line">
                    Address: {{ props.address }}
                </p>
            </div>
            <Card>
                <template #content>
                    <form
                        class="flex flex-col gap-5"
                        @submit.prevent="submit"
                    >
                        <div class="flex flex-col gap-2">
                            <label for="contact-name">Name</label>
                            <InputText
                                id="contact-name"
                                v-model="form.name"
                                autocomplete="name"
                                maxlength="255"
                                required
                                :invalid="Boolean(form.errors.name)"
                                fluid
                            />
                            <InputErrors :errors="form.errors.name" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="contact-email">Email</label>
                            <InputText
                                id="contact-email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                maxlength="255"
                                required
                                :invalid="Boolean(form.errors.email)"
                                fluid
                            />
                            <InputErrors :errors="form.errors.email" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="contact-message">Message</label>
                            <Textarea
                                id="contact-message"
                                v-model="form.message"
                                rows="6"
                                maxlength="5000"
                                required
                                autoResize
                                :invalid="Boolean(form.errors.message)"
                                fluid
                            />
                            <InputErrors :errors="form.errors.message" />
                        </div>
                        <Button
                            type="submit"
                            label="Send message"
                            :loading="form.processing"
                            :disabled="form.processing"
                            class="self-end"
                        />
                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
