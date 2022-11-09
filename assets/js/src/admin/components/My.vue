<!-- CustomInput.vue -->
<script setup>
//defineProps(['modelValue'])
//defineEmits(['update:modelValue'])
defineProps(['message'])
defineEmits(['update:message'])
</script>

<template>
 <!-- <input
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />-->
  <input
    :value="message"
    @input="$emit('update:message', $event.target.value)"
  />
</template>