<script setup>
const config = useRuntimeConfig()
defineProps({
  data: {
    type: Object,
    required: true
  }
})
</script>
<style>
.list-enter-active,
.list-leave-active {
  @apply transition-all duration-700 ease-in-out  transform translate-x-0 opacity-100
}

.list-enter-from,
.list-leave-to {
  @apply opacity-0 translate-x-full
}
</style>
<template>
  <section class="relative h-full overflow-hidden rounded-xl border border-dashed border-gray-400 opacity-100">
    <div class="mx-auto max-w-2xl px-4 py-4 sm:px-4 sm:py-4 lg:max-w-full lg:px-4">
      <TransitionGroup name="list" tag="ul"
                       class="grid grid-cols-1 gap-y-4 lg:grid-cols-3 sm:gap-x-6 sm:gap-y-4 lg:gap-x-4">
        <li class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white45"
            v-for="offer in data" :key="offer.codeCgt">
          <VisitItem :offer="offer"/>
        </li>
      </TransitionGroup>
    </div>
  </section>
</template>