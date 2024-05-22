<script setup>
import {IconNavigation, IconWalk, IconLink, IconGraph, IconClock} from "@tabler/icons-vue";

const config = useRuntimeConfig()
const {offer} = defineProps({
  offer: {
    type: Object,
    required: true
  }
})

const image = computed(() => {
  if (offer.images.length > 0) {
    return offer.images[0]
  }

  return 'https://picsum.photos/1280'
})

</script>
<template>
  <article>
    <div class="aspect-square w-full bg-gray-200 group-hover:opacity-75 h-80 md:h-96">
      <img :src="image"
           :alt="offer.nom"
           class="h-full w-full object-cover object-center transition-transform duration-500 ease-in-out group-hover:scale-105">
    </div>
    <div class="flex flex-1 flex-col space-y-2 p-4 h-60">
      <h3 class="text-xl text-carto-gray300 roboto-bold">
        <NuxtLink :to="offer.url" target="_blank">
          <span aria-hidden="true" class="absolute inset-0"></span>
          {{ offer.nom }}
        </NuxtLink>
      </h3>
      <p class="flex flex-row" v-if="offer.gpx_duree">
        <IconClock class="w-6 h-6"/>
        {{ offer.gpx_duree }}
      </p>
      <p class="flex flex-row" v-if="offer.gpx_difficulte">
        <IconGraph class="w-6 h-6"/>
        {{ offer.gpx_difficulte }}
      </p>
      <p class="flex flex-row" v-if="offer.gpx_distance">
        <IconWalk class="w-6 h-6"/>
        {{ offer.gpx_distance }} km
      </p>
      <div class="flex flex-1 flex-col justify-end">
        <p class="text-base font-medium text-gray-900">
           {{ offer.address.rue }}
          {{ offer.address.lieuPrecis }}
        </p>
        <p class="text-sm italic text-gray-500">{{ offer.localite }}</p>
      </div>
    </div>
  </article>
</template>