<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    errors?: string | string[]
}>()

// Inertia sends one message per field as a string unless all errors are requested.
const messages = computed(() => {
    if (!props.errors) {
        return []
    }

    return Array.isArray(props.errors) ? props.errors : [props.errors]
})
</script>

<template>
    <div
        v-if="messages.length"
        class="space-y-1"
    >
        <Message
            v-for="(errorMessage, index) in messages"
            :key="index"
            severity="error"
            variant="simple"
            size="small"
        >
            {{ errorMessage }}
        </Message>
    </div>
</template>
