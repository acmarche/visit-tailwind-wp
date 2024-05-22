<script setup>
const previewOpen = defineModel('previewOpen')
defineProps({
  name: {
    type: String,
    required: false
  },
})
</script>
<template>
  <header class="h-28 bg-carto-green text-white flex flex-col w-full p-3">
    <div class="flex flex-row justify-between ">
      <h2 class="text-2xl lobster-two-bold">{{ name ?? 'Chargement' }}</h2>
      <button type="button"
              class="-mr-2 flex h-10 w-10 items-center justify-center p-2 text-white hover:text-carto-gray300"
              @click="previewOpen = false">
        <span class="sr-only">Fermer le menu</span>
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
             stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <slot></slot>
  </header>
</template>